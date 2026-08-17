<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\AssignsRolesAndPermissions;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use AssignsRolesAndPermissions, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('user_roles')->insertOrIgnore([
            ['id' => 1, 'role_name' => 'Admin'],
            ['id' => 2, 'role_name' => 'Nurse'],
            ['id' => 3, 'role_name' => 'Midwife'],
            ['id' => 4, 'role_name' => 'BHW'],
        ]);

        DB::table('permissions')->insertOrIgnore([
            ['name' => 'users', 'description' => 'Access to User Management', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'household', 'description' => 'Access to Household module', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function createAdmin(): User
    {
        return $this->createUserWithPermissions(['users']);
    }

    private function createBhw(): User
    {
        return $this->createUserWithRole('BHW', ['username' => 'bhw_'.bin2hex(random_bytes(4))]);
    }

    private function attachBhwPermissions(int $userId): void
    {
        $roleId = User::findOrFail($userId)->role_id;

        DB::table('role_permissions')->insertOrIgnore([
            'role_id' => $roleId,
            'permission_id' => DB::table('permissions')->where('name', 'household')->value('id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_admin_can_disable_a_bhw_user(): void
    {
        $admin = $this->createAdmin();
        $bhw = $this->createBhw();
        $this->attachBhwPermissions($bhw->id);

        $this->actingAs($admin);

        $this->get(route('users.index'))
            ->assertOk()
            ->assertSee('confirmDisableUser('.$bhw->id.')')
            ->assertDontSee('confirmDisableUser('.$admin->id.')');

        $this->post(route('users.disable', $bhw), ['password' => 'password'])
            ->assertRedirect(route('users.index'));

        $this->assertFalse($bhw->fresh()->is_active);
    }

    public function test_disable_requires_the_admins_current_password(): void
    {
        $admin = $this->createAdmin();
        $bhw = $this->createBhw();
        $this->attachBhwPermissions($bhw->id);

        $this->actingAs($admin);

        $this->post(route('users.disable', $bhw))
            ->assertSessionHasErrors('password');

        $this->assertTrue($bhw->fresh()->is_active);

        $this->post(route('users.disable', $bhw), ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');

        $this->assertTrue($bhw->fresh()->is_active);
    }

    public function test_admin_accounts_cannot_be_disabled(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createUserWithPermissions(['users']);

        $this->actingAs($admin);

        $this->post(route('users.disable', $otherAdmin), ['password' => 'password'])
            ->assertRedirect(route('users.index'));

        $this->assertTrue($otherAdmin->fresh()->is_active);
    }

    public function test_admin_cannot_change_own_role(): void
    {
        $admin = $this->createAdmin();
        $adminRoleId = $admin->role_id;
        $bhwRoleId = DB::table('user_roles')->where('role_name', 'BHW')->value('id');

        $this->actingAs($admin);

        $this->put(route('users.update', $admin), [
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'username' => $admin->username,
            'email' => $admin->email,
            'role_id' => $bhwRoleId,
        ])->assertRedirect(route('users.index'));

        $this->assertSame($adminRoleId, $admin->fresh()->role_id);
    }

    public function test_edit_page_disables_role_dropdown_for_own_account(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('users.edit', $admin))
            ->assertOk()
            ->assertSee('You cannot change your own role');
    }

    public function test_edit_page_keeps_role_dropdown_enabled_for_other_users(): void
    {
        $admin = $this->createAdmin();
        $bhw = $this->createBhw();

        $this->actingAs($admin)
            ->get(route('users.edit', $bhw))
            ->assertOk()
            ->assertDontSee('You cannot change your own role');
    }
}
