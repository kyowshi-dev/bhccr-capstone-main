<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AssignInitialRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Assigns permissions to roles (not to individual users).
     */
    public function run(): void
    {
        $rolePermissionMap = [
            'Admin' => Permission::query()->pluck('name')->toArray(),
            'Doctor' => ['patients', 'consultations', 'medicines', 'print_handouts', 'dashboard_handouts_clinical'],
            'Nurse' => ['patients', 'consultations', 'medicines', 'immunizations', 'print_handouts', 'dashboard_handouts_clinical'],
            'Midwife' => ['patients', 'consultations', 'immunizations', 'reports', 'print_handouts', 'dashboard_handouts_clinical', 'dashboard_handouts_midwife'],
            'BHW' => ['household', 'patients', 'consultations', 'reports', 'print_handouts', 'dashboard_handouts_bhw'],
        ];

        foreach ($rolePermissionMap as $roleName => $permissionNames) {
            $role = Role::query()->where('role_name', $roleName)->first();

            if ($role === null) {
                continue;
            }

            $permissionIds = Permission::query()
                ->whereIn('name', $permissionNames, 'and', false)
                ->pluck('id')
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}
