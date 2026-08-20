<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PDO;
use Symfony\Component\Process\Process;

final class BackupService
{
    public static function databaseInfo(): array
    {
        $driver = config('database.default');

        return [
            'driver' => $driver,
            'databaseName' => config('database.connections.'.$driver)['database'] ?? $driver,
        ];
    }

    /**
     * Build a downloadable backup for the current driver.
     * Returns ['download' => [...]] / ['stream' => [...]] on success or ['error' => string].
     */
    public static function export(): array
    {
        $driver = config('database.default');
        $filename = 'bhcis-backup-'.now()->format('Y-m-d-His');

        if ($driver === 'sqlite') {
            $path = config('database.connections.sqlite.database');
            if (! is_file($path)) {
                return ['error' => 'Database file not found.'];
            }

            if (! is_readable($path)) {
                return ['error' => 'Database file is not readable. Check file permissions.'];
            }

            return [
                'download' => [
                    'path' => $path,
                    'filename' => $filename.'.sqlite',
                    'contentType' => 'application/octet-stream',
                ],
            ];
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $conn = config('database.connections.'.$driver);
            $process = new Process(self::mysqldumpCommand($conn), null, self::credentialsEnv($conn));
            $process->setTimeout(600); // 10 minutes timeout for large databases
            $process->run();

            if (! $process->isSuccessful()) {
                $stderr = trim($process->getErrorOutput());
                Log::error('mysqldump failed: '.$stderr);

                $userMessage = 'Backup failed. ';
                if (str_contains($stderr, 'Access denied')) {
                    $userMessage .= 'Database credentials are incorrect. Check the DB_USERNAME and DB_PASSWORD in .env.';
                } elseif (str_contains($stderr, 'Command not found') || str_contains($stderr, 'No such file')) {
                    $userMessage .= 'mysqldump is not installed or not on the system PATH.';
                } elseif (str_contains($stderr, 'Cannot connect')) {
                    $userMessage .= 'Cannot connect to the database server. Check DB_HOST and DB_PORT in .env.';
                } else {
                    $userMessage .= 'Ensure mysqldump is installed and database credentials are correct.';
                }

                return ['error' => $userMessage];
            }

            $sqlContent = $process->getOutput();
            if (empty($sqlContent)) {
                return ['error' => 'Backup completed but no data was exported. Check database connection.'];
            }

            return [
                'stream' => [
                    'content' => $sqlContent,
                    'filename' => $filename.'.sql',
                    'contentType' => 'application/sql',
                ],
            ];
        }

        return ['error' => 'Unsupported database driver for export.'];
    }

    /**
     * Import an uploaded backup for the current driver.
     * Returns ['success' => true] or ['error' => string].
     */
    public static function import(UploadedFile $file): array
    {
        $driver = config('database.default');

        if ($driver === 'sqlite') {
            return self::importSqlite($file);
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return self::importMysql($file);
        }

        return ['error' => 'Unsupported database driver for import.'];
    }

    private static function importSqlite(UploadedFile $file): array
    {
        $dbPath = config('database.connections.sqlite.database');
        $dbDir = dirname($dbPath);

        $fileError = self::sqliteFileError($file->getRealPath(), self::currentSqliteTables(), $file->getClientOriginalExtension());
        if ($fileError !== null) {
            return ['error' => $fileError];
        }

        if (! is_writable($dbDir)) {
            return ['error' => 'Database directory is not writable. Check file permissions on '.htmlspecialchars($dbDir).'.'];
        }

        // Create backup of current database
        if (file_exists($dbPath)) {
            $backupPath = $dbPath.'.backup.'.now()->format('Y-m-d-His');
            if (! copy($dbPath, $backupPath)) {
                return ['error' => 'Failed to create backup of current database.'];
            }
        }

        // Move uploaded file to database location
        try {
            $file->move($dbDir, basename($dbPath));

            // Verify the new database file exists and is readable
            if (! is_file($dbPath)) {
                return ['error' => 'Database file was not created at the expected location. The import may have partially completed.'];
            }

            if (! is_readable($dbPath)) {
                return ['error' => 'Database file was created but is not readable. Check file permissions.'];
            }

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('SQLite import failed: '.$e->getMessage());

            return ['error' => 'Failed to import database: '.$e->getMessage()];
        }
    }

    private static function importMysql(UploadedFile $file): array
    {
        $driver = config('database.default');
        $conn = config('database.connections.'.$driver);
        $sqlContent = file_get_contents($file->getRealPath());

        $dumpError = self::mysqlDumpError($sqlContent);
        if ($dumpError !== null) {
            return ['error' => $dumpError];
        }

        // Capture the current schema before touching anything so we can reject
        // outdated or mismatched dumps after the import.
        try {
            $pdo = self::mysqlPdo($conn);
            $currentTables = self::mysqlTableNames($pdo);
        } catch (\PDOException $e) {
            Log::error('Unable to connect before MySQL import: '.$e->getMessage());

            return ['error' => 'Unable to connect to the database to validate the import. Check DB_HOST, DB_PORT and credentials in .env.'];
        }

        // Create backup of current database first
        $env = self::credentialsEnv($conn);
        $backupFilename = 'bhcis-backup-pre-import-'.now()->format('Y-m-d-His').'.sql';
        $backupProcess = new Process(self::mysqldumpCommand($conn), null, $env);
        $backupProcess->setTimeout(300);
        $backupProcess->run();

        $backupCreated = false;
        if ($backupProcess->isSuccessful()) {
            Storage::put($backupFilename, $backupProcess->getOutput());
            $backupCreated = true;
        } else {
            Log::warning('Pre-import backup failed, proceeding with import anyway: '.$backupProcess->getErrorOutput());
        }

        // Import the new database
        $importCommand = self::mysqlImportCommand($conn);
        $importProcess = new Process($importCommand, null, $env);
        $importProcess->setInput($sqlContent);
        $importProcess->setTimeout(600); // 10 minutes timeout for large databases
        $importProcess->run();

        if (! $importProcess->isSuccessful()) {
            $stderr = trim($importProcess->getErrorOutput());
            Log::error('MySQL import failed: '.$stderr);

            $recoveryNote = $backupCreated
                ? ' A backup of the previous database was saved as '.$backupFilename.' in storage.'
                : ' No pre-import backup could be created.';

            $userMessage = 'Import failed.'.$recoveryNote;
            if (str_contains($stderr, 'Access denied')) {
                $userMessage .= ' Database credentials are incorrect.';
            } elseif (str_contains($stderr, 'Command not found') || str_contains($stderr, 'No such file')) {
                $userMessage .= ' The mysql client is not installed or not on the system PATH.';
            } elseif (str_contains($stderr, 'ERROR')) {
                // Extract the first MySQL error line
                $errorLines = array_filter(explode("\n", $stderr), static fn ($line) => str_starts_with($line, 'ERROR'));
                if (! empty($errorLines)) {
                    $userMessage .= ' '.reset($errorLines);
                }
            }

            return ['error' => $userMessage];
        }

        // Verify the import by attempting to connect and query the database,
        // and reject outdated/incompatible dumps that are missing tables the
        // application needs.
        try {
            $pdo = self::mysqlPdo($conn);
            $pdo->query('SELECT 1');

            $missing = self::missingTables($currentTables, self::mysqlTableNames($pdo));
            if ($missing !== []) {
                $recoveryNote = $backupCreated
                    ? ' A backup of the previous database was saved as '.$backupFilename.'.'
                    : ' No pre-import backup was available.';

                return ['error' => 'Import completed but the imported database is missing required tables ('.implode(', ', $missing).'). It may be outdated or from a different system.'.$recoveryNote.' You may need to restore from backup manually.'];
            }
        } catch (\PDOException $e) {
            Log::error('Post-import verification failed: '.$e->getMessage());
            $recoveryNote = $backupCreated
                ? ' A backup of the previous database was saved as '.$backupFilename.'.'
                : ' No pre-import backup was available.';

            return ['error' => 'Import completed but database verification failed.'.$recoveryNote.' You may need to restore from backup manually.'];
        }

        return ['success' => true];
    }

    /**
     * Validate an uploaded SQLite file without touching the live database.
     * Returns an error message, or null when the file is safe to import.
     */
    public static function sqliteFileError(string $path, array $currentTables, string $extension = ''): ?string
    {
        $magicBytes = file_get_contents($path, false, null, 0, 16);
        if ($magicBytes !== "SQLite format 3\000" && $magicBytes !== "SQLite format 3\001") {
            if (strtolower($extension) === 'sql') {
                return 'SQLite database does not support importing .sql files. Use a .sqlite or .db backup file, or switch to MySQL.';
            }

            return 'The uploaded file does not appear to be a valid SQLite database.';
        }

        try {
            $pdo = new PDO('sqlite:'.$path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $integrity = $pdo->query('PRAGMA integrity_check')->fetchColumn();
        } catch (\PDOException $e) {
            Log::error('SQLite import integrity check failed: '.$e->getMessage());

            return 'The uploaded database is corrupt and failed its integrity check, so it was rejected.';
        }

        if ($integrity !== 'ok') {
            return 'The uploaded database is corrupt and failed its integrity check, so it was rejected.';
        }

        try {
            $uploadedTables = array_values(array_filter(
                array_map(
                    static fn ($row) => (string) $row['name'],
                    $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'")->fetchAll(PDO::FETCH_ASSOC)
                ),
                static fn (string $name) => ! str_starts_with($name, 'sqlite_')
            ));
        } catch (\PDOException $e) {
            Log::error('SQLite import validation failed: '.$e->getMessage());

            return 'The uploaded file could not be read as a SQLite database.';
        }

        $missing = self::missingTables($currentTables, $uploadedTables);
        if ($missing !== []) {
            return 'The uploaded database is incompatible with this application and is missing required tables ('.implode(', ', $missing).'). It may be outdated or from a different system.';
        }

        return null;
    }

    /**
     * Validate the content of an uploaded MySQL dump without executing it.
     * Returns an error message, or null when the dump is safe to import.
     */
    public static function mysqlDumpError(string $content): ?string
    {
        if (empty($content)) {
            return 'The uploaded file is empty or invalid.';
        }

        // Cheap pre-check before the full statement scan
        if (! str_contains($content, 'CREATE') && ! str_contains($content, 'INSERT') && ! str_contains($content, '-- MySQL')) {
            return 'The uploaded file does not appear to be a valid SQL dump.';
        }

        foreach (self::sqlStatements($content) as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }

            $statementError = self::mysqlStatementError($statement);
            if ($statementError !== null) {
                return $statementError;
            }
        }

        return null;
    }

    private static function currentSqliteTables(): array
    {
        return array_values(array_filter(
            DB::table('sqlite_master')->where('type', 'table')->pluck('name')->all(),
            static fn ($name) => ! str_starts_with((string) $name, 'sqlite_')
        ));
    }

    private static function mysqlPdo(array $conn): PDO
    {
        $driver = config('database.default');
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s',
            $driver === 'mariadb' ? 'mysql' : $driver,
            $conn['host'],
            $conn['port'],
            $conn['database']
        );

        return new PDO($dsn, $conn['username'], $conn['password'] ?: null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    private static function mysqlTableNames(PDO $pdo): array
    {
        $rows = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);

        return array_map(static fn (array $row) => strtolower((string) $row[0]), $rows);
    }

    /**
     * Tables that exist in the current database but are missing from the
     * candidate database. A missing table means an outdated or foreign dump.
     */
    public static function missingTables(array $current, array $uploaded): array
    {
        $current = array_map('strtolower', array_values($current));
        $uploaded = array_map('strtolower', array_values($uploaded));

        return array_values(array_diff($current, $uploaded));
    }

    /**
     * Split a SQL dump into statements after stripping string literals,
     * identifiers, and comments so they cannot hide malicious SQL.
     */
    private static function sqlStatements(string $sql): array
    {
        $code = self::stripSqlLiteralsAndComments($sql);
        $statements = [];
        foreach (explode(';', $code) as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $statements[] = $statement;
            }
        }

        return $statements;
    }

    private static function mysqlStatementError(string $statement): ?string
    {
        $allowedPatterns = [
            '/^set\b/i',
            '/^create\s+table\b/i',
            '/^insert\b/i',
            '/^lock\s+tables\b/i',
            '/^unlock\s+tables\b/i',
            '/^begin\b/i',
            '/^commit\b/i',
            '/^drop\s+table\s+if\s+exists\b/i',
            '/^drop\s+temporary\s+table\s+if\s+exists\b/i',
        ];
        foreach ($allowedPatterns as $pattern) {
            if (preg_match($pattern, $statement)) {
                return null;
            }
        }

        // mysqldump emits ALTER TABLE ... DISABLE/ENABLE KEYS for MyISAM tables
        if (preg_match('/^alter\s+table\b/i', $statement) && preg_match('/\b(?:disable|enable)\s+keys\b/i', $statement)) {
            return null;
        }

        return 'The uploaded SQL dump contains an unsupported or dangerous statement: "'.trim(substr($statement, 0, 100)).'". It was not imported.';
    }

    /**
     * Remove string literals, backtick identifiers, line comments, and
     * non-executable block comments from SQL so dangerous keywords cannot be
     * hidden inside them. Content of versioned comments (/*!##### ... *&#47;) is
     * kept because MySQL executes it.
     */
    private static function stripSqlLiteralsAndComments(string $sql): string
    {
        $out = '';
        $length = strlen($sql);
        $i = 0;

        while ($i < $length) {
            $char = $sql[$i];

            if ($char === "'" || $char === '"') {
                $i = self::skipSqlQuoted($sql, $i, $char);
                $out .= ' ';

                continue;
            }

            if ($char === '`') {
                $i = self::skipSqlQuoted($sql, $i, '`');
                $out .= ' ';

                continue;
            }

            if ($char === '#' || ($char === '-' && ($sql[$i + 1] ?? '') === '-' && (($sql[$i + 2] ?? '') === ' ' || ($sql[$i + 2] ?? '') === "\t" || ($sql[$i + 2] ?? '') === "\n" || ($sql[$i + 2] ?? '') === "\r" || $i + 2 >= $length))) {
                while ($i < $length && $sql[$i] !== "\n") {
                    $i++;
                }
                $out .= ' ';

                continue;
            }

            if ($char === '/' && ($sql[$i + 1] ?? '') === '*') {
                $closing = strpos($sql, '*/', $i + 2);
                $end = $closing === false ? $length : $closing + 2;

                if (($sql[$i + 2] ?? '') === '!') {
                    // Versioned executable comment - scan its content for danger
                    $inner = substr($sql, $i + 3, $closing === false ? $length - $i - 3 : $closing - $i - 3);
                    $inner = preg_replace('/^[0-9]+/', '', $inner);
                    $out .= self::stripSqlLiteralsAndComments($inner).' ';
                }

                $i = $end;

                continue;
            }

            $out .= $char;
            $i++;
        }

        return $out;
    }

    private static function skipSqlQuoted(string $sql, int $i, string $quote): int
    {
        $length = strlen($sql);
        $i++;

        while ($i < $length) {
            if ($sql[$i] === $quote) {
                if ($i + 1 < $length && $sql[$i + 1] === $quote) {
                    $i += 2;

                    continue;
                }

                return $i + 1;
            }

            if ($sql[$i] === '\\' && $i + 1 < $length) {
                $i += 2;

                continue;
            }

            $i++;
        }

        return $length;
    }

    private static function mysqldumpCommand(array $conn): array
    {
        $cmd = [
            'mysqldump',
            '--no-defaults', // MUST be the first option; mysql tools reject it anywhere else
            '-h', $conn['host'],
            '-P', (string) $conn['port'],
            '-u', $conn['username'],
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--skip-column-statistics',
        ];

        // Explicitly pass empty password flag to prevent interactive prompt
        if (empty($conn['password'])) {
            $cmd[] = '--password=';
        }

        $cmd[] = $conn['database'];

        return $cmd;
    }

    private static function mysqlImportCommand(array $conn): array
    {
        $cmd = [
            'mysql',
            '--no-defaults', // MUST be the first option; mysql tools reject it anywhere else
            '-h', $conn['host'],
            '-P', (string) $conn['port'],
            '-u', $conn['username'],
            '--batch',       // Non-interactive, tab-separated output
        ];

        // Explicitly pass empty password flag to prevent interactive prompt
        if (empty($conn['password'])) {
            $cmd[] = '--password=';
        }

        $cmd[] = $conn['database'];

        return $cmd;
    }

    private static function credentialsEnv(array $conn): array
    {
        return ! empty($conn['password']) ? ['MYSQL_PWD' => $conn['password']] : [];
    }
}
