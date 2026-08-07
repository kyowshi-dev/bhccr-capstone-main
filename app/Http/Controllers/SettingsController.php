<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function updateAccount(Request $request): RedirectResponse
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
        return view('settings.backups', BackupService::databaseInfo());
    }

    public function exportBackup(Request $request): RedirectResponse|BinaryFileResponse|StreamedResponse
    {
        $this->confirmCurrentPassword($request);

        $result = BackupService::export();

        if (isset($result['error'])) {
            $this->recordAudit('backup_export_failed', $request);

            return redirect()
                ->route('settings.backups')
                ->with('error', $result['error']);
        }

        $this->recordAudit('backup_exported', $request);

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

    public function importBackup(Request $request): RedirectResponse
    {
        $this->confirmCurrentPassword($request);

        $request->validate([
            'backup_file' => ['required', 'file', 'max:51200', 'extensions:sql,sqlite,db'], // 50MB max
        ]);

        $result = BackupService::import($request->file('backup_file'));

        $this->recordAudit($result['success'] ?? null ? 'backup_imported' : 'backup_import_failed', $request);

        if (isset($result['error'])) {
            return redirect()
                ->route('settings.backups')
                ->with('error', $result['error']);
        }

        return redirect()
            ->route('settings.backups')
            ->with('success', 'Database imported successfully. A backup of the previous database was created.');
    }

    /**
     * Require the authenticated user's password before sensitive operations.
     */
    private function confirmCurrentPassword(Request $request): void
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) use ($user): void {
                if ($user === null || ! Hash::check($value, $user->password)) {
                    $fail('The current password is incorrect.');
                }
            }],
        ]);
    }

    /**
     * Record a backup operation in the audit trail (no PII stored).
     */
    private function recordAudit(string $action, Request $request): void
    {
        AuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'table_name' => 'backups',
            'record_id' => null,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }
}
