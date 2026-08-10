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
            'Doctor' => ['patients', 'consultations', 'medicines', 'maternal', 'maternal.view_queues', 'maternal.log_visit', 'maternal.manage_watchlist', 'print_handouts', 'dashboard_handouts_clinical'],

            // NURSE
            'Nurse' => ['patients', 'consultations', 'medicines', 'immunizations', 'maternal', 'maternal.view_queues', 'maternal.log_visit', 'maternal.manage_watchlist', 'print_handouts', 'dashboard_handouts_clinical'],

            // MIDWIFE
            'Midwife' => ['patients', 'consultations', 'immunizations', 'maternal', 'maternal.view_queues', 'maternal.log_visit', 'maternal.manage_watchlist', 'reports', 'print_handouts', 'dashboard_handouts_clinical', 'dashboard_handouts_midwife'],

            // BHW
            'BHW' => ['household', 'patients', 'consultations', 'immunizations', 'maternal', 'maternal.view_queues', 'reports', 'print_handouts', 'dashboard_handouts_bhw'],
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
