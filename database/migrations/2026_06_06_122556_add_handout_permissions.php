<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $permissions = [
        'print_handouts' => 'Print consultation Rx and diagnosis handouts',
        'dashboard_handouts_bhw' => 'BHW dashboard — Results ready panel',
        'dashboard_handouts_clinical' => 'Clinical dashboard — Recent completed handouts panel',
        'dashboard_handouts_admin' => 'Admin dashboard — Completed consultations handouts panel',
    ];

    public function up(): void
    {
        $permissionIds = [];

        foreach ($this->permissions as $name => $description) {
            $existing = DB::table('permissions')->where('name', $name)->first();

            if ($existing) {
                DB::table('permissions')->where('id', $existing->id)->update([
                    'description' => $description,
                    'updated_at' => now(),
                ]);
                $permissionIds[$name] = $existing->id;

                continue;
            }

            $permissionIds[$name] = DB::table('permissions')->insertGetId([
                'name' => $name,
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $rolePermissionMap = [
            'bhw' => ['print_handouts', 'dashboard_handouts_bhw'],
            'nurse' => ['print_handouts', 'dashboard_handouts_clinical'],
            'doctor' => ['print_handouts', 'dashboard_handouts_clinical'],
            'midwife' => ['print_handouts', 'dashboard_handouts_clinical'],
            'admin' => ['print_handouts', 'dashboard_handouts_admin', 'dashboard_handouts_bhw', 'dashboard_handouts_clinical'],
            'head nurse' => ['print_handouts', 'dashboard_handouts_admin', 'dashboard_handouts_clinical'],
        ];

        $workers = DB::table('health_workers')->select('user_id', 'role')->get();

        foreach ($workers as $worker) {
            $role = strtolower((string) $worker->role);
            $names = null;

            foreach ($rolePermissionMap as $needle => $perms) {
                if (str_contains($role, $needle)) {
                    $names = $perms;
                    break;
                }
            }

            if ($names === null) {
                continue;
            }

            foreach ($names as $permissionName) {
                $permissionId = $permissionIds[$permissionName] ?? null;
                if ($permissionId === null) {
                    continue;
                }

                $exists = DB::table('users_permissions')
                    ->where('user_id', $worker->user_id)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $exists) {
                    DB::table('users_permissions')->insert([
                        'user_id' => $worker->user_id,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }

        $adminUsers = DB::table('users_permissions')
            ->join('permissions', 'users_permissions.permission_id', '=', 'permissions.id')
            ->where('permissions.name', 'users')
            ->pluck('users_permissions.user_id');

        foreach ($adminUsers as $userId) {
            foreach (['print_handouts', 'dashboard_handouts_admin'] as $permissionName) {
                $permissionId = $permissionIds[$permissionName];
                $exists = DB::table('users_permissions')
                    ->where('user_id', $userId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (! $exists) {
                    DB::table('users_permissions')->insert([
                        'user_id' => $userId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $names = array_keys($this->permissions);
        $ids = DB::table('permissions')->whereIn('name', $names)->pluck('id');

        if ($ids->isNotEmpty()) {
            DB::table('users_permissions')->whereIn('permission_id', $ids)->delete();
            DB::table('permissions')->whereIn('id', $ids)->delete();
        }
    }
};
