<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Cache;

final class NotificationService
{
    /**
     * Send an in-app notification to each recipient and invalidate the
     * header dropdown caches so the bell badge updates immediately.
     *
     * @param  User|iterable<User>  $recipients
     */
    public static function send(User|iterable $recipients, string $type, string $title, string $message, ?string $url = null): void
    {
        $recipients = is_iterable($recipients) ? $recipients : [$recipients];

        foreach ($recipients as $recipient) {
            $recipient->notify(new AppNotification($type, $title, $message, $url));

            self::forgetHeaderCache($recipient->id);
        }
    }

    /**
     * Notify every active user holding a permission, optionally limited to
     * zone-scoped users whose assigned zones contain one of the given patient
     * ids so zone BHWs never receive out-of-zone alerts.
     *
     * @param  list<int>|null  $patientIds
     */
    public static function sendToPermissionHolders(string $permission, string $type, string $title, string $message, ?string $url = null, ?array $patientIds = null, ?int $excludeUserId = null): void
    {
        $recipients = User::query()
            ->where('is_active', true)
            ->with('role.permissions')
            ->get()
            ->filter(fn (User $user) => $user->hasPermission($permission))
            ->filter(fn (User $user) => $user->id !== $excludeUserId)
            ->filter(function (User $user) use ($patientIds) {
                if (! $user->isZoneScoped()) {
                    return true;
                }

                if ($patientIds === null) {
                    return false;
                }

                return array_intersect($patientIds, $user->accessiblePatientIds()) !== [];
            });

        self::send($recipients, $type, $title, $message, $url);
    }

    /**
     * Invalidate the header dropdown caches for a user so the bell badge and
     * recent list never serve stale data after a change.
     */
    public static function forgetHeaderCache(int $userId): void
    {
        Cache::forget("header_notifications_{$userId}");
        Cache::forget("header_unread_count_{$userId}");
    }
}
