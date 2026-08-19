<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;

class NotificationController extends Controller
{
    /**
     * Display all notifications for the user
     */
    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(pageSize(15))->withQueryString();

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark a specific notification as read
     */
    public function markRead($notificationId)
    {
        $notification = auth()->user()->notifications()->findOrFail($notificationId);
        $notification->markAsRead();

        $this->forgetHeaderCache();

        return back()->with('success', 'Notification marked as read');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllRead()
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        $this->forgetHeaderCache();

        return back()->with('success', 'All notifications marked as read');
    }

    /**
     * Delete a specific notification
     */
    public function destroy($notificationId)
    {
        $notification = auth()->user()->notifications()->findOrFail($notificationId);
        $notification->delete();

        $this->forgetHeaderCache();

        return back()->with('success', 'Notification deleted');
    }

    /**
     * Delete all notifications
     */
    public function destroyAll()
    {
        auth()->user()->notifications()->delete();

        $this->forgetHeaderCache();

        return back()->with('success', 'All notifications deleted');
    }

    /**
     * Invalidate the header dropdown caches for the authenticated user so
     * the bell badge and recent list never serve stale data after a change.
     */
    private function forgetHeaderCache(): void
    {
        $user = auth()->user();

        Cache::forget("header_notifications_{$user->id}");
        Cache::forget("header_unread_count_{$user->id}");
    }
}
