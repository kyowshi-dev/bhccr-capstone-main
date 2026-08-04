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
            'Admin' => Permission::pluck('name')->toArray(),
            'Doctor' => ['patients', 'consultations', 'medicines', 'print_handouts', 'dashboard_handouts_clinical'],
            'Nurse' => ['patients', 'consultations', 'medicines', 'print_handouts', 'dashboard_handouts_clinical'],
            'Midwife' => ['patients', 'consultations', 'immunizations', 'reports', 'print_handouts', 'dashboard_handouts_clinical', 'dashboard_handouts_midwife'],
            'BHW' => ['household', 'patients', 'consultations', 'reports', 'print_handouts', 'dashboard_handouts_bhw'],
            'BNS' => ['patients', 'immunizations', 'reports'],
            'User' => [],
        ];

        foreach ($rolePermissionMap as $roleName => $permissionNames) {
            $role = Role::query()->where('role_name', $roleName)->first();

            if ($role === null) {
                continue;
            }

            $role->permissions()->sync(Permission::whereIn('name', $permissionNames)->pluck('id'));
        }
    }
}
