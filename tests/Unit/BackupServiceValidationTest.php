<?php

namespace Tests\Unit;

use App\Services\BackupService;
use Tests\TestCase;

class BackupServiceValidationTest extends TestCase
{
    private function tempFile(string $suffix = '.sqlite'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'bhcis-backup-test');

        return $path.$suffix;
    }

    private function createSqliteDatabase(array $tables, ?string $path = null): string
    {
        $path ??= $this->tempFile();

        $pdo = new \PDO('sqlite:'.$path);
        foreach ($tables as $table) {
            $pdo->exec('CREATE TABLE '.$table.' (id INTEGER PRIMARY KEY)');
        }

        return $path;
    }

    public function test_sqlite_file_error_rejects_non_sqlite_file(): void
    {
        $path = $this->tempFile();
        file_put_contents($path, 'this is definitely not a sqlite database');

        $error = BackupService::sqliteFileError($path, ['users']);

        $this->assertNotNull($error);
        $this->assertStringContainsString('does not appear to be a valid SQLite database', $error);
    }

    public function test_sqlite_file_error_rejects_sql_extension(): void
    {
        $path = $this->tempFile('.sql');
        file_put_contents($path, 'SELECT 1;');

        $error = BackupService::sqliteFileError($path, ['users'], 'sql');

        $this->assertNotNull($error);
        $this->assertStringContainsString('does not support importing .sql files', $error);
    }

    public function test_sqlite_file_error_accepts_valid_matching_database(): void
    {
        $path = $this->createSqliteDatabase(['users', 'patients']);

        $this->assertNull(BackupService::sqliteFileError($path, ['users', 'patients']));
    }

    public function test_sqlite_file_error_rejects_corrupt_database(): void
    {
        $path = $this->createSqliteDatabase(['users']);
        $fh = fopen($path, 'r+b');
        fseek($fh, 16);
        fwrite($fh, pack('n', 1024));
        fclose($fh);

        $error = BackupService::sqliteFileError($path, ['users']);

        $this->assertNotNull($error);
        $this->assertStringContainsString('integrity check', $error);
    }

    public function test_sqlite_file_error_rejects_outdated_database(): void
    {
        $path = $this->createSqliteDatabase(['users']);

        $error = BackupService::sqliteFileError($path, ['users', 'patients']);

        $this->assertNotNull($error);
        $this->assertStringContainsString('missing required tables', $error);
        $this->assertStringContainsString('patients', $error);
    }

    public function test_sqlite_file_error_accepts_database_with_extra_tables(): void
    {
        $path = $this->createSqliteDatabase(['users', 'patients', 'future_table']);

        $this->assertNull(BackupService::sqliteFileError($path, ['users', 'patients']));
    }

    public function test_mysql_dump_error_accepts_mysqldump_style_dump(): void
    {
        $dump = <<<'SQL'
            -- MySQL dump 10.13
            /*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
            /*!40000 ALTER TABLE `users` DISABLE KEYS */;
            DROP TABLE IF EXISTS `users`;
            CREATE TABLE `users` (
              `id` bigint unsigned NOT NULL AUTO_INCREMENT,
              `name` varchar(255) DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB;
            LOCK TABLES `users` WRITE;
            INSERT INTO `users` VALUES (1,'Admin');
            UNLOCK TABLES;
            /*!40000 ALTER TABLE `users` ENABLE KEYS */;
            SQL;

        $this->assertNull(BackupService::mysqlDumpError($dump));
    }

    public function test_mysql_dump_error_accepts_keyword_inside_data_string(): void
    {
        $dump = "CREATE TABLE `notes` (`note` text);\nINSERT INTO `notes` VALUES ('Grant wrote: DROP DATABASE is scary but this is just a note');";

        $this->assertNull(BackupService::mysqlDumpError($dump));
    }

    public function test_mysql_dump_error_rejects_drop_database(): void
    {
        $dump = "CREATE TABLE `users` (`id` int);\nDROP DATABASE `bhcis`;";

        $error = BackupService::mysqlDumpError($dump);

        $this->assertNotNull($error);
        $this->assertStringContainsString('dangerous statement', $error);
    }

    public function test_mysql_dump_error_rejects_grant(): void
    {
        $dump = "CREATE TABLE `users` (`id` int);\nGRANT ALL ON *.* TO 'hacker'@'%';";

        $this->assertNotNull(BackupService::mysqlDumpError($dump));
    }

    public function test_mysql_dump_error_rejects_bare_drop_table(): void
    {
        $dump = 'DROP TABLE users;';

        $this->assertNotNull(BackupService::mysqlDumpError($dump));
    }

    public function test_mysql_dump_error_rejects_select_into_outfile(): void
    {
        $dump = "CREATE TABLE `users` (`id` int);\nSELECT * INTO OUTFILE '/tmp/evil.txt' FROM users;";

        $this->assertNotNull(BackupService::mysqlDumpError($dump));
    }

    public function test_mysql_dump_error_rejects_delete_and_update(): void
    {
        $dump = "CREATE TABLE `users` (`id` int);\nDELETE FROM users;";
        $this->assertNotNull(BackupService::mysqlDumpError($dump));

        $dump2 = "CREATE TABLE `users` (`id` int);\nUPDATE users SET id = 1;";
        $this->assertNotNull(BackupService::mysqlDumpError($dump2));
    }

    public function test_mysql_dump_error_rejects_use_statement(): void
    {
        $dump = "CREATE TABLE `users` (`id` int);\nUSE other_database;";

        $this->assertNotNull(BackupService::mysqlDumpError($dump));
    }

    public function test_mysql_dump_error_rejects_trigger_and_procedure(): void
    {
        $dump = "CREATE TABLE `users` (`id` int);\nCREATE TRIGGER evil AFTER INSERT ON users BEGIN DELETE FROM users; END;";
        $this->assertNotNull(BackupService::mysqlDumpError($dump));

        $dump2 = "CREATE TABLE `users` (`id` int);\nCREATE PROCEDURE evil() BEGIN SELECT 1; END;";
        $this->assertNotNull(BackupService::mysqlDumpError($dump2));
    }

    public function test_mysql_dump_error_rejects_alter_table_add_column(): void
    {
        $dump = "CREATE TABLE `users` (`id` int);\nALTER TABLE `users` ADD COLUMN `evil` text;";

        $this->assertNotNull(BackupService::mysqlDumpError($dump));
    }

    public function test_mysql_dump_error_rejects_empty_file(): void
    {
        $this->assertNotNull(BackupService::mysqlDumpError(''));
    }

    public function test_mysql_dump_error_rejects_non_sql_file(): void
    {
        $this->assertNotNull(BackupService::mysqlDumpError('plain text, no sql keywords'));
    }

    public function test_missing_tables_returns_only_missing(): void
    {
        $this->assertSame(['patients'], BackupService::missingTables(['users', 'patients'], ['users', 'visits']));
        $this->assertSame([], BackupService::missingTables(['users', 'patients'], ['users', 'patients', 'extra']));
        $this->assertSame([], BackupService::missingTables([], ['users']));
    }

    public function test_missing_tables_is_case_insensitive(): void
    {
        $this->assertSame([], BackupService::missingTables(['Users'], ['users']));
    }
}
