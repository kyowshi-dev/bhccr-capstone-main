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
                'username' => 'bhw',
                'email' => 'bhw@bhcis.local',
                'password' => Hash::make('password123'),
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'role' => 'BHW',
                'role_id' => 4,
                'contact_number' => '09171234568',
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
                'role_id' => 6,
                'contact_number' => '09171234570',
            ],
        ];

        foreach ($users as $userData) {
            $first_name = $userData['first_name'];
            $last_name = $userData['last_name'];
            $role = $userData['role'];
            $contact_number = $userData['contact_number'];

            // Check if user already exists
            $existingUser = User::where('username', $userData['username'])->first();
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
