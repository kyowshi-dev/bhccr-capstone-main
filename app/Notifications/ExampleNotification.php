<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Example notification class
 * 
 * Usage in your application:
 * use App\Notifications\ExampleNotification;
 * 
 * $user->notify(new ExampleNotification('Title', 'Message'));
 */
class ExampleNotification extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        private string $title,
        private string $message
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
        ];
    }
}
