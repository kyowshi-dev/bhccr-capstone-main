<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            $process->setTimeout(300); // 5 minutes timeout
            $process->run();

            if (! $process->isSuccessful()) {
                return ['error' => 'Backup failed: '.$process->getErrorOutput().'. Ensure mysqldump is installed and database credentials are correct.'];
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

                return ['success' => true];
            } catch (\Exception $e) {
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

            // Create backup of current database first
            $env = self::credentialsEnv($conn);
            $backupFilename = 'bhcis-backup-pre-import-'.now()->format('Y-m-d-His').'.sql';
            $backupProcess = new Process(self::mysqldumpCommand($conn), null, $env);
            $backupProcess->setTimeout(120);
            $backupProcess->run();

            if ($backupProcess->isSuccessful()) {
                Storage::put($backupFilename, $backupProcess->getOutput());
            }

            // Now import the new database
            $importCommand = [
                'mysql',
                '-h', $conn['host'],
                '-P', (string) $conn['port'],
                '-u', $conn['username'],
                $conn['database'],
            ];

            $importProcess = new Process($importCommand, null, $env);
            $importProcess->setInput($sqlContent);
            $importProcess->setTimeout(300); // 5 minutes timeout for import
            $importProcess->run();

            if (! $importProcess->isSuccessful()) {
                return ['error' => 'Import failed. A backup was created before import. Error: '.$importProcess->getErrorOutput()];
            }

            return ['success' => true];
        }

        return ['error' => 'Unsupported database driver for import.'];
    }

    private static function mysqldumpCommand(array $conn): array
    {
        return [
            'mysqldump',
            '-h', $conn['host'],
            '-P', (string) $conn['port'],
            '-u', $conn['username'],
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            $conn['database'],
        ];
    }

    private static function credentialsEnv(array $conn): array
    {
        return ! empty($conn['password']) ? ['MYSQL_PWD' => $conn['password']] : [];
    }
}
