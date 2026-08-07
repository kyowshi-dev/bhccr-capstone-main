<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const PERMISSION = 'maternal';

    public function up(): void
    {
        $existing = DB::table('permissions')->where('name', self::PERMISSION)->first();

        if ($existing) {
            DB::table('permissions')->where('id', $existing->id)->update([
                'description' => 'Family Planning, Prenatal, and Postnatal maternal care services',
                'updated_at' => now(),
            ]);
            $permissionId = (int) $existing->id;
        } else {
            $permissionId = (int) DB::table('permissions')->insertGetId([
                'name' => self::PERMISSION,
                'description' => 'Family Planning, Prenatal, and Postnatal maternal care services',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $roleIds = DB::table('user_roles')
            ->whereIn('role_name', ['Midwife', 'Nurse', 'Admin'])
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            $exists = DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $exists) {
                DB::table('role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('name', self::PERMISSION)->value('id');

        if ($id === null) {
            return;
        }

        DB::table('role_permissions')->where('permission_id', $id)->delete();
        DB::table('permissions')->where('id', $id)->delete();
    }
};
