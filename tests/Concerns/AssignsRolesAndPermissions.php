<?php

namespace Tests\Concerns;

use App\Models\User;
use Illuminate\Support\Facades\DB;

trait AssignsRolesAndPermissions
{
    /**
     * Create a user holding a role linked to the given permission names.
     *
     * @param  list<string>  $permissions
     * @param  array<string, mixed>  $attributes
     */
    private function createUserWithPermissions(array $permissions = [], array $attributes = []): User
    {
        $roleId = $this->createRoleWithPermissions($permissions);

        $user = User::factory()->create(array_merge($attributes, ['role_id' => $roleId]));

        return $user;
    }

    /**
     * Create a user holding the given role name (permissions resolved from role_permissions).
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createUserWithRole(string $roleName, array $attributes = []): User
    {
        $roleId = DB::table('user_roles')->where('role_name', $roleName)->value('id');

        if ($roleId === null) {
            $roleId = DB::table('user_roles')->insertGetId(['role_name' => $roleName]);
        }

        $user = User::factory()->create(array_merge($attributes, ['role_id' => $roleId]));

        return $user;
    }

    /**
     * Create a role with a specific name linked to the given permission names and return the user holding it.
     *
     * @param  list<string>  $permissions
     * @param  array<string, mixed>  $attributes
     */
    private function createUserWithNamedRole(string $roleName, array $permissions = [], array $attributes = []): User
    {
        $roleId = DB::table('user_roles')->where('role_name', $roleName)->value('id');

        if ($roleId === null) {
            $roleId = DB::table('user_roles')->insertGetId(['role_name' => $roleName]);
        }

        foreach ($permissions as $permissionName) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permissionName,
                'description' => 'Test permission: '.$permissionName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return User::factory()->create(array_merge($attributes, ['role_id' => $roleId]));
    }

    /**
     * Create a role linked to the given permission names and return its id.
     *
     * @param  list<string>  $permissions
     */
    private function createRoleWithPermissions(array $permissions): int
    {
        $roleId = DB::table('user_roles')->insertGetId([
            'role_name' => 'Role '.bin2hex(random_bytes(4)),
        ]);

        foreach ($permissions as $permissionName) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permissionName,
                'description' => 'Test permission: '.$permissionName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permissionIds = DB::table('permissions')->whereIn('name', $permissions)->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $roleId;
    }
}
