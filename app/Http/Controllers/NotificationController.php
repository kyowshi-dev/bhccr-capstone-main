<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Display all notifications for the user
     */
    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(15);
        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark a specific notification as read
     */
    public function markRead($notificationId)
    {
        $notification = auth()->user()->notifications()->findOrFail($notificationId);
        $notification->markAsRead();
        
        return back()->with('success', 'Notification marked as read');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        
        return back()->with('success', 'All notifications marked as read');
    }

    /**
     * Delete a specific notification
     */
    public function destroy($notificationId)
    {
        $notification = auth()->user()->notifications()->findOrFail($notificationId);
        $notification->delete();
        
        return back()->with('success', 'Notification deleted');
    }

    /**
     * Delete all notifications
     */
    public function destroyAll()
    {
        auth()->user()->notifications()->delete();
        
        return back()->with('success', 'All notifications deleted');
    }
}
