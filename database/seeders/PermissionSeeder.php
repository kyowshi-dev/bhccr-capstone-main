<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            ['name' => 'household', 'description' => 'Access to Household module'],
            ['name' => 'patients', 'description' => 'Access to Patients module'],
            ['name' => 'consultations', 'description' => 'Access to Consultations module'],
            ['name' => 'immunizations', 'description' => 'Access to Immunizations module'],
            ['name' => 'medicines', 'description' => 'Access to Medicines module'],
            ['name' => 'reports', 'description' => 'Access to Reports module'],
            ['name' => 'users', 'description' => 'Access to User Management'],
            ['name' => 'zones', 'description' => 'Manage geographic zones and assign health workers'],
            ['name' => 'maternal', 'description' => 'Access to Maternal Health module (prenatal, postnatal, family planning)'],
            ['name' => 'print_handouts', 'description' => 'Print consultation Rx and diagnosis handouts'],
            ['name' => 'dashboard_handouts', 'description' => 'View completed handouts panel on dashboards'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }
}
