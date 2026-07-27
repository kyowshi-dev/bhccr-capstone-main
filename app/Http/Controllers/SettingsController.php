<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index');
    }

    public function account()
    {
        return view('settings.account', [
            'user' => Auth::user(),
        ]);
    }

    public function updateAccount(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) use ($user) {
                if (! Hash::check($value, $user->password)) {
                    $fail('The current password is incorrect.');
                }
            }],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Current password is required.',
            'password.required' => 'New password is required.',
            'password.min' => 'New password must be at least 8 characters.',
            'password.confirmed' => 'New password confirmation does not match.',
        ]);

        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()
            ->route('settings.account')
            ->with('success', 'Your password has been updated.');
    }

    public function backups()
    {
        $driver = config('database.default');
        $connection = config('database.connections.'.$driver);

        return view('settings.backups', [
            'driver' => $driver,
            'databaseName' => $connection['database'] ?? $driver,
        ]);
    }

    public function exportBackup(Request $request)
    {
        $driver = config('database.default');
        $filename = 'bhcis-backup-'.now()->format('Y-m-d-His');

        if ($driver === 'sqlite') {
            $path = config('database.connections.sqlite.database');
            if (! is_file($path)) {
                return redirect()
                    ->route('settings.backups')
                    ->with('error', 'Database file not found.');
            }
            $filename .= '.sqlite';

            return response()->download($path, $filename, [
                'Content-Type' => 'application/octet-stream',
            ]);
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $conn = config('database.connections.'.$driver);
            $filename .= '.sql';
            $command = [
                'mysqldump',
                '-h', $conn['host'],
                '-P', (string) $conn['port'],
                '-u', $conn['username'],
                '--single-transaction',
                '--quick',
                '--skip-lock-tables',
                $conn['database'],
            ];

            // Handle password securely
            $env = [];
            if (! empty($conn['password'])) {
                $env['MYSQL_PWD'] = $conn['password'];
            }

            $process = new Process($command, null, $env);
            $process->setTimeout(300); // 5 minutes timeout
            $process->run();

            if (! $process->isSuccessful()) {
                $error = $process->getErrorOutput();

                return redirect()
                    ->route('settings.backups')
                    ->with('error', 'Backup failed: '.$error.'. Ensure mysqldump is installed and database credentials are correct.');
            }

            $sqlContent = $process->getOutput();
            if (empty($sqlContent)) {
                return redirect()
                    ->route('settings.backups')
                    ->with('error', 'Backup completed but no data was exported. Check database connection.');
            }

            return response()->streamDownload(
                static function () use ($sqlContent): void {
                    echo $sqlContent;
                },
                $filename,
                [
                    'Content-Type' => 'application/sql',
                ],
            );
        }

        return redirect()
            ->route('settings.backups')
            ->with('error', 'Unsupported database driver for export.');
    }

    public function importBackup(Request $request)
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'max:51200'], // 50MB max
        ]);

        $driver = config('database.default');
        $file = $request->file('backup_file');

        if ($driver === 'sqlite') {
            // For SQLite, replace the entire database file
            $dbPath = config('database.connections.sqlite.database');
            $dbDir = dirname($dbPath);

            // Create backup of current database
            if (file_exists($dbPath)) {
                $backupPath = $dbPath.'.backup.'.now()->format('Y-m-d-His');
                if (! copy($dbPath, $backupPath)) {
                    return redirect()
                        ->route('settings.backups')
                        ->with('error', 'Failed to create backup of current database.');
                }
            }

            // Move uploaded file to database location
            try {
                $file->move($dbDir, basename($dbPath));

                return redirect()
                    ->route('settings.backups')
                    ->with('success', 'Database imported successfully. A backup of the previous database was created.');
            } catch (\Exception $e) {
                return redirect()
                    ->route('settings.backups')
                    ->with('error', 'Failed to import database: '.$e->getMessage());
            }
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            // For MySQL/MariaDB, use mysql command to import SQL file
            $conn = config('database.connections.'.$driver);
            $sqlContent = file_get_contents($file->getRealPath());

            if (empty($sqlContent)) {
                return redirect()
                    ->route('settings.backups')
                    ->with('error', 'The uploaded file is empty or invalid.');
            }

            // Create backup of current database first
            $backupFilename = 'bhcis-backup-pre-import-'.now()->format('Y-m-d-His').'.sql';
            $backupCommand = [
                'mysqldump',
                '-h', $conn['host'],
                '-P', (string) $conn['port'],
                '-u', $conn['username'],
                '--single-transaction',
                '--quick',
                '--skip-lock-tables',
                $conn['database'],
            ];
            $env = [];
            if (! empty($conn['password'])) {
                $env['MYSQL_PWD'] = $conn['password'];
            }
            $backupProcess = new Process($backupCommand, null, $env);
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
                return redirect()
                    ->route('settings.backups')
                    ->with('error', 'Import failed. A backup was created before import. Error: '.$importProcess->getErrorOutput());
            }

            return redirect()
                ->route('settings.backups')
                ->with('success', 'Database imported successfully. A backup of the previous database was created.');
        }

        return redirect()
            ->route('settings.backups')
            ->with('error', 'Unsupported database driver for import.');
    }
}
