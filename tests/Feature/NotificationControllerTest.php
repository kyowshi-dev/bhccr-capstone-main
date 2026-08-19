<?php

namespace Tests\Feature;

use App\Notifications\AppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    public function test_index_lists_notifications_for_the_user(): void
    {
        $user = $this->createUserWithPermissions();

        $user->notify(new AppNotification('test_type', 'Test title', 'Test message'));

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Test title')
            ->assertSee('Test message');
    }

    public function test_mark_read_marks_notification_and_busts_header_cache(): void
    {
        $user = $this->createUserWithPermissions();

        $user->notify(new AppNotification('test_type', 'Test title', 'Test message'));

        $notification = $user->notifications()->first();

        Cache::put("header_unread_count_{$user->id}", 1);
        Cache::put("header_notifications_{$user->id}", ['stale']);

        $this->actingAs($user)
            ->post(route('notifications.mark-read', $notification->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull($user->notifications()->first()->read_at);
        $this->assertFalse(Cache::has("header_unread_count_{$user->id}"));
        $this->assertFalse(Cache::has("header_notifications_{$user->id}"));
    }

    public function test_mark_all_read_marks_every_unread_notification(): void
    {
        $user = $this->createUserWithPermissions();

        $user->notify(new AppNotification('test_type', 'Title one', 'Message one'));
        $user->notify(new AppNotification('test_type', 'Title two', 'Message two'));

        $this->actingAs($user)
            ->post(route('notifications.mark-all-read'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(2, $user->notifications()->whereNotNull('read_at')->count());
    }

    public function test_destroy_deletes_a_notification(): void
    {
        $user = $this->createUserWithPermissions();

        $user->notify(new AppNotification('test_type', 'Test title', 'Test message'));

        $notification = $user->notifications()->first();

        $this->actingAs($user)
            ->delete(route('notifications.destroy', $notification->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_destroy_all_clears_all_notifications(): void
    {
        $user = $this->createUserWithPermissions();

        $user->notify(new AppNotification('test_type', 'Title one', 'Message one'));
        $user->notify(new AppNotification('test_type', 'Title two', 'Message two'));

        $this->actingAs($user)
            ->post(route('notifications.destroy-all'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(0, $user->notifications()->count());
    }

    public function test_mark_read_returns_404_for_another_users_notification(): void
    {
        $owner = $this->createUserWithPermissions();
        $other = $this->createUserWithPermissions();

        $owner->notify(new AppNotification('test_type', 'Test title', 'Test message'));

        $notification = $owner->notifications()->first();

        $this->actingAs($other)
            ->post(route('notifications.mark-read', $notification->id))
            ->assertNotFound();

        $this->assertNull($owner->notifications()->first()->read_at);
    }
}
