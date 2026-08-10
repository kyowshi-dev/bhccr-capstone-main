<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportBackupRequest;
use App\Http\Requests\RestrictUserRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Services\AuditLogService;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {}

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

    public function updateAccount(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $validated = $request->validated();

        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()
            ->route('settings.account')
            ->with('success', 'Your password has been updated.');
    }

    public function backups()
    {
        return view('settings.backups', BackupService::databaseInfo());
    }

    public function exportBackup(RestrictUserRequest $request): RedirectResponse|BinaryFileResponse|StreamedResponse
    {
        $result = BackupService::export();

        if (isset($result['error'])) {
            $this->audit->log('backup_export_failed', 'backups', $request);

            return redirect()
                ->route('settings.backups')
                ->with('error', $result['error']);
        }

        $this->audit->log('backup_exported', 'backups', $request);

        if (isset($result['download'])) {
            return response()->download(
                $result['download']['path'],
                $result['download']['filename'],
                ['Content-Type' => $result['download']['contentType']]
            );
        }

        return response()->streamDownload(
            static function () use ($result): void {
                echo $result['stream']['content'];
            },
            $result['stream']['filename'],
            ['Content-Type' => $result['stream']['contentType']]
        );
    }

    public function importBackup(ImportBackupRequest $request): RedirectResponse
    {
        $result = BackupService::import($request->file('backup_file'));

        $this->audit->log($result['success'] ?? null ? 'backup_imported' : 'backup_import_failed', 'backups', $request);

        if (isset($result['error'])) {
            return redirect()
                ->route('settings.backups')
                ->with('error', $result['error']);
        }

        return redirect()
            ->route('settings.backups')
            ->with('success', 'Database imported successfully. A backup of the previous database was created.');
    }
}
