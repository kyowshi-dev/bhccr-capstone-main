<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateInitialUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Define users with their roles and details
        $users = [
            [
                'username' => 'admin',
                'email' => 'admin@bhcis.local',
                'password' => Hash::make('password123'),
                'first_name' => 'System',
                'last_name' => 'Administrator',
                'role' => 'Administrator',
                'role_id' => 1,
                'contact_number' => '09171234567',
            ],
            [
                'username' => 'bhw1',
                'email' => 'bhw1@bhcis.local',
                'password' => Hash::make('password123'),
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'role' => 'BHW',
                'role_id' => 4,
                'contact_number' => '09171234568',
            ],
            [
                'username' => 'bhw2',
                'email' => 'bhw2@bhcis.local',
                'password' => Hash::make('password123'),
                'first_name' => 'Elena',
                'last_name' => 'Cruz',
                'role' => 'BHW',
                'role_id' => 4,
                'contact_number' => '09181234561',
            ],
            [
                'username' => 'bhw3',
                'email' => 'bhw3@bhcis.local',
                'password' => Hash::make('password123'),
                'first_name' => 'Josefina',
                'last_name' => 'Garcia',
                'role' => 'BHW',
                'role_id' => 4,
                'contact_number' => '09181234562',
            ],
            [
                'username' => 'bhw4',
                'email' => 'bhw4@bhcis.local',
                'password' => Hash::make('password123'),
                'first_name' => 'Lorna',
                'last_name' => 'Villanueva',
                'role' => 'BHW',
                'role_id' => 4,
                'contact_number' => '09181234563',
            ],
            [
                'username' => 'bhw5',
                'email' => 'bhw5@bhcis.local',
                'password' => Hash::make('password123'),
                'first_name' => 'Teresa',
                'last_name' => 'Mendoza',
                'role' => 'BHW',
                'role_id' => 4,
                'contact_number' => '09181234564',
            ],
            [
                'username' => 'bhw6',
                'email' => 'bhw6@bhcis.local',
                'password' => Hash::make('password123'),
                'first_name' => 'Gloria',
                'last_name' => 'Aquino',
                'role' => 'BHW',
                'role_id' => 4,
                'contact_number' => '09181234565',
            ],
            [
                'username' => 'bhw7',
                'email' => 'bhw7@bhcis.local',
                'password' => Hash::make('password123'),
                'first_name' => 'Imelda',
                'last_name' => 'Ramos',
                'role' => 'BHW',
                'role_id' => 4,
                'contact_number' => '09181234566',
            ],
            [
                'username' => 'bhw8',
                'email' => 'bhw8@bhcis.local',
                'password' => Hash::make('password123'),
                'first_name' => 'Nenita',
                'last_name' => 'Torres',
                'role' => 'BHW',
                'role_id' => 4,
                'contact_number' => '09181234567',
            ],
            [
                'username' => 'midwife',
                'email' => 'midwife2@bhcis.local',
                'password' => Hash::make('password123'),
                'first_name' => 'Rosario',
                'last_name' => 'Bautista',
                'role' => 'Midwife',
                'role_id' => 3,
                'contact_number' => '09191234572',
            ],
            [
                'username' => 'nurse',
                'email' => 'nurse@bhcis.local',
                'password' => Hash::make('password123'),
                'first_name' => 'John',
                'last_name' => 'Reyes',
                'role' => 'Nurse',
                'role_id' => 2,
                'contact_number' => '09171234569',
            ],
            [
                'username' => 'doctor',
                'email' => 'doctor@bhcis.local',
                'password' => Hash::make('password123'),
                'first_name' => 'Dr. Carlos',
                'last_name' => 'Garcia',
                'role' => 'Doctor',
                'role_id' => 5,
                'contact_number' => '09171234570',
            ],
        ];

        foreach ($users as $userData) {
            $first_name = $userData['first_name'];
            $last_name = $userData['last_name'];
            $role = $userData['role'];
            $contact_number = $userData['contact_number'];

            // Check if user already exists
            $existingUser = User::where('username', '=', $userData['username'])->first();
            if ($existingUser) {
                $this->command->warn("User '{$userData['username']}' already exists. Skipping...");

                continue;
            }

            // Create user
            $user = User::create([
                'username' => $userData['username'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'is_active' => true,
                'role_id' => $userData['role_id'],
            ]);

            // Create health worker record
            DB::table('health_workers')->insert([
                'user_id' => $user->id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'role' => $role,
                'contact_number' => $contact_number,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info("User '{$userData['username']}' created successfully with role '{$role}'.");
        }
    }
}
