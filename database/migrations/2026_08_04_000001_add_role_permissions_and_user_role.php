<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string> lower-case role string needle => canonical role name
     */
    private array $roleMap = [
        'admin' => 'Admin',
        'administrator' => 'Admin',
        'head nurse' => 'Admin',
        'nurse' => 'Nurse',
        'doctor' => 'Doctor',
        'midwife' => 'Midwife',
        'bhw' => 'BHW',
        'bns' => 'BNS',
    ];

    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('user_roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->unique(['role_id', 'permission_id']);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('is_active')->constrained('user_roles')->nullOnDelete();
        });

        $this->ensureRoles();
        $this->assignUsersToRoles();
        $this->backfillRolePermissions();

        Schema::dropIfExists('users_permissions');
    }

    public function down(): void
    {
        Schema::create('users_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->unique(['user_id', 'permission_id']);
            $table->timestamps();
        });

        $rows = DB::table('role_permissions')
            ->join('users', 'users.role_id', '=', 'role_permissions.role_id')
            ->select('users.id as user_id', 'role_permissions.permission_id')
            ->get();

        foreach ($rows as $row) {
            $exists = DB::table('users_permissions')
                ->where('user_id', $row->user_id)
                ->where('permission_id', $row->permission_id)
                ->exists();

            if (! $exists) {
                DB::table('users_permissions')->insert([
                    'user_id' => $row->user_id,
                    'permission_id' => $row->permission_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::dropIfExists('role_permissions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
    }

    private function ensureRoles(): void
    {
        foreach (['Admin', 'Nurse', 'Midwife', 'BHW', 'BNS', 'Doctor', 'User'] as $roleName) {
            $exists = DB::table('user_roles')->where('role_name', $roleName)->exists();

            if (! $exists) {
                DB::table('user_roles')->insert(['role_name' => $roleName]);
            }
        }
    }

    private function assignUsersToRoles(): void
    {
        $workers = DB::table('health_workers')->select('user_id', 'role')->get();

        foreach ($workers as $worker) {
            $roleName = $this->roleNameFor($worker->user_id, (string) $worker->role);

            if ($roleName === null) {
                continue;
            }

            $roleId = DB::table('user_roles')->where('role_name', $roleName)->value('id');

            if ($roleId !== null) {
                DB::table('users')->where('id', $worker->user_id)->update(['role_id' => $roleId]);
            }
        }
    }

    private function roleNameFor(int $userId, string $workerRole): ?string
    {
        $normalized = strtolower(trim($workerRole));

        foreach ($this->roleMap as $needle => $roleName) {
            if (str_contains($normalized, $needle)) {
                return $roleName;
            }
        }

        $userPermissionNames = DB::table('users_permissions')
            ->join('permissions', 'users_permissions.permission_id', '=', 'permissions.id')
            ->where('users_permissions.user_id', $userId)
            ->pluck('permissions.name')
            ->all();

        if (in_array('users', $userPermissionNames, true)) {
            return 'Admin';
        }

        if (in_array('dashboard_handouts_bhw', $userPermissionNames, true)) {
            return 'BHW';
        }

        return 'User';
    }

    private function backfillRolePermissions(): void
    {
        $permissionIdsByRole = DB::table('users_permissions')
            ->join('users', 'users_permissions.user_id', '=', 'users.id')
            ->join('permissions', 'users_permissions.permission_id', '=', 'permissions.id')
            ->select('users.role_id', 'permissions.name')
            ->get()
            ->filter(fn ($row) => $row->role_id !== null)
            ->groupBy('role_id')
            ->map(function ($rows) {
                return DB::table('permissions')
                    ->whereIn('name', $rows->pluck('name')->unique())
                    ->pluck('id');
            });

        foreach ($permissionIdsByRole as $roleId => $permissionIds) {
            foreach ($permissionIds as $permissionId) {
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
    }
};
