<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The export/import routes share one throttle counter per user
        // (throttle:3,60), and the array cache persists across tests in the
        // same process. Reset it so the backup tests are not throttled.
        Cache::flush();
    }

    public function test_guest_cannot_access_settings(): void
    {
        $response = $this->get(route('settings.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_settings_index(): void
    {
        $user = User::query()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Account management');
        $response->assertDontSee('Export the database for backup or transfer');
    }

    public function test_user_with_users_permission_sees_backups_card(): void
    {
        $user = $this->createUserWithPermissions(['users']);

        $response = $this->actingAs($user)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertSee('Account management');
        $response->assertSee('Backups');
    }

    public function test_authenticated_user_can_access_account_but_not_backups_pages(): void
    {
        $user = $this->createUserWithPermissions(['users']);

        $this->actingAs($user)->get(route('settings.account'))->assertStatus(200);
        $this->actingAs($user)->get(route('settings.backups'))->assertStatus(200);
    }

    public function test_user_without_users_permission_cannot_access_backups(): void
    {
        $user = User::query()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('settings.backups'))->assertStatus(403);
    }

    public function test_user_can_update_password(): void
    {
        $user = User::query()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('oldpass'),

            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('settings.account.update'), [
            'current_password' => 'oldpass',
            'password' => 'NewPassword8',
            'password_confirmation' => 'NewPassword8',
        ]);

        $response->assertRedirect(route('settings.account'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword8', $user->password));
    }

    public function test_update_password_fails_with_wrong_current_password(): void
    {
        $user = User::query()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('oldpass'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('settings.account.update'), [
            'current_password' => 'wrongpass',
            'password' => 'newpassword8',
            'password_confirmation' => 'newpassword8',
        ]);

        $response->assertSessionHasErrors('current_password');
        $user->refresh();
        $this->assertTrue(Hash::check('oldpass', $user->password));
    }

    public function test_export_backup_requires_current_password(): void
    {
        $user = $this->createUserWithPermissions(['users']);

        $this->actingAs($user)
            ->post(route('settings.backups.export'))
            ->assertSessionHasErrors('current_password');
    }

    public function test_export_backup_rejects_wrong_password(): void
    {
        $user = $this->createUserWithPermissions(['users']);

        $this->actingAs($user)
            ->post(route('settings.backups.export'), ['current_password' => 'wrong-password'])
            ->assertSessionHasErrors('current_password');
    }

    public function test_export_backup_with_correct_password_reaches_service(): void
    {
        $user = $this->createUserWithPermissions(['users']);

        // The test database is in-memory sqlite (":memory:"), so the service
        // runs and reports that there is no database file. The key assertion is
        // that validation passes (no current_password error) and the controller
        // handles the service result instead of silently failing.
        $this->actingAs($user)
            ->post(route('settings.backups.export'), ['current_password' => 'password'])
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('error');
    }

    public function test_import_backup_requires_current_password(): void
    {
        $user = $this->createUserWithPermissions(['users']);

        $this->actingAs($user)
            ->post(route('settings.backups.import'))
            ->assertSessionHasErrors(['current_password', 'backup_file']);
    }
}
