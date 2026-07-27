<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AssignInitialPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $roleName = DB::table('health_workers')
                ->where('user_id', $user->id)
                ->value('role');

            if ($roleName === null) {
                continue;
            }

            $permissions = [];
            $normalizedRole = strtolower($roleName);

            if (str_contains($normalizedRole, 'admin') || str_contains($normalizedRole, 'head nurse')) {
                $user->permissions()->sync(Permission::pluck('id'));

                continue;
            } elseif (str_contains($normalizedRole, 'nurse')) {
                $permissions = ['patients', 'consultations', 'medicines', 'print_handouts', 'dashboard_handouts_clinical'];
            } elseif (str_contains($normalizedRole, 'bhw')) {
                $permissions = ['household', 'patients', 'consultations', 'reports', 'print_handouts', 'dashboard_handouts_bhw'];
            } elseif (str_contains($normalizedRole, 'midwife')) {
                $permissions = ['patients', 'consultations', 'immunizations', 'reports', 'print_handouts', 'dashboard_handouts_clinical'];
            } elseif (str_contains($normalizedRole, 'doctor')) {
                $permissions = ['patients', 'consultations', 'medicines', 'print_handouts', 'dashboard_handouts_clinical'];
            }

            if (! empty($permissions)) {
                $permissionIds = Permission::whereIn('name', $permissions)->pluck('id');
                $user->permissions()->sync($permissionIds);
            }
        }
    }
}
