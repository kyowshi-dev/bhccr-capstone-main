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
            // ADMIN
            'Admin' => Permission::query()->pluck('name')->toArray(),

            // DOCTOR
            'Doctor' => ['patients', 'consultations', 'medicines', 'maternal', 'print_handouts', 'dashboard_handouts'],

            // NURSE
            'Nurse' => ['patients', 'consultations', 'medicines', 'immunizations', 'maternal', 'print_handouts', 'dashboard_handouts'],

            // MIDWIFE
            'Midwife' => ['patients', 'consultations', 'immunizations', 'maternal', 'reports', 'print_handouts', 'dashboard_handouts'],

            // BHW
            'BHW' => ['household', 'patients', 'consultations', 'immunizations', 'maternal', 'reports', 'print_handouts', 'dashboard_handouts'],
        ];

        foreach ($rolePermissionMap as $roleName => $permissionNames) {
            $role = Role::query()->where('role_name', $roleName)->first();

            if ($role === null) {
                continue;
            }

            $permissionIds = Permission::query()
                ->whereIn('name', $permissionNames)
                ->pluck('id')
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }
}
