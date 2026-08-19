<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class UserCreationNotificationTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_new_user_notifies_admins_except_the_new_user(): void
    {
        $admin = $this->createUserWithPermissions(['users']);
        $staff = $this->createUserWithPermissions(['consultations']);

        $roleId = DB::table('user_roles')->insertGetId(['role_name' => 'Nurse '.bin2hex(random_bytes(4))]);

        UserManagementService::create([
            'username' => 'new.nurse',
            'email' => 'nurse@example.com',
            'password' => 'StrongPass!123',
            'role_id' => $roleId,
            'first_name' => 'New',
            'last_name' => 'Nurse',
            'contact_number' => null,
        ]);

        $newUser = User::where('username', 'new.nurse')->firstOrFail();

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(0, $staff->notifications()->count());
        $this->assertSame(0, $newUser->notifications()->count());

        $notification = $admin->notifications()->first();

        $this->assertSame('user_created', $notification->data['type']);
        $this->assertStringContainsString('new.nurse', $notification->data['title']);
        $this->assertStringContainsString('Nurse', $notification->data['title']);
        $this->assertSame(route('users.edit', $newUser->id), $notification->data['url']);
    }
}
