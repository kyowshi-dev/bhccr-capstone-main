<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
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
            // For SQLite, replace the entire database file
            $dbPath = config('database.connections.sqlite.database');
            $dbDir = dirname($dbPath);

            // Verify the uploaded file is a valid SQLite database
            $magicBytes = file_get_contents($file->getRealPath(), false, null, 0, 16);
            if ($magicBytes !== "SQLite format 3\000" && $magicBytes !== "SQLite format 3\001") {
                // Allow .sql files too — they might be SQL dumps rather than raw SQLite files
                $ext = strtolower($file->getClientOriginalExtension());
                if ($ext === 'sql') {
                    return ['error' => 'SQLite database does not support importing .sql files. Use a .sqlite or .db backup file, or switch to MySQL.'];
                }

                return ['error' => 'The uploaded file does not appear to be a valid SQLite database.'];
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

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            // For MySQL/MariaDB, use mysql command to import SQL file
            $conn = config('database.connections.'.$driver);
            $sqlContent = file_get_contents($file->getRealPath());

            if (empty($sqlContent)) {
                return ['error' => 'The uploaded file is empty or invalid.'];
            }

            // Validate that the file looks like a SQL dump
            if (! str_contains($sqlContent, 'CREATE') && ! str_contains($sqlContent, 'INSERT') && ! str_contains($sqlContent, '-- MySQL')) {
                return ['error' => 'The uploaded file does not appear to be a valid SQL dump.'];
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

            // Verify the import by attempting to connect and query the database
            try {
                $dsn = sprintf(
                    '%s:host=%s;port=%s;dbname=%s',
                    $driver === 'mariadb' ? 'mysql' : $driver,
                    $conn['host'],
                    $conn['port'],
                    $conn['database']
                );
                $pdo = new PDO($dsn, $conn['username'], $conn['password'] ?: null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                $pdo->query('SELECT 1');
            } catch (\PDOException $e) {
                Log::error('Post-import verification failed: '.$e->getMessage());
                $recoveryNote = $backupCreated
                    ? ' A backup of the previous database was saved as '.$backupFilename.'.'
                    : ' No pre-import backup was available.';

                return ['error' => 'Import completed but database verification failed.'.$recoveryNote.' You may need to restore from backup manually.'];
            }

            return ['success' => true];
        }

        return ['error' => 'Unsupported database driver for import.'];
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
