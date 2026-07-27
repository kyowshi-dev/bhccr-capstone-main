<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $logs = [
            [
                'user_id' => 1,
                'action' => 'created',
                'table_name' => 'patients',
                'record_id' => 1,
                'old_values' => null,
                'new_values' => ['first_name' => 'Juan', 'last_name' => 'Dela Cruz'],
                'ip_address' => '127.0.0.1',
                'created_at' => Carbon::now()->subHours(2),
            ],
            [
                'user_id' => 1,
                'action' => 'created',
                'table_name' => 'consultations',
                'record_id' => 1,
                'old_values' => null,
                'new_values' => ['status' => 'triage'],
                'ip_address' => '127.0.0.1',
                'created_at' => Carbon::now()->subHours(1),
            ],
            [
                'user_id' => 1,
                'action' => 'updated',
                'table_name' => 'consultations',
                'record_id' => 1,
                'old_values' => ['status' => 'triage'],
                'new_values' => ['status' => 'completed'],
                'ip_address' => '127.0.0.1',
                'created_at' => Carbon::now()->subMinutes(30),
            ],
            [
                'user_id' => 1,
                'action' => 'created',
                'table_name' => 'patients',
                'record_id' => 2,
                'old_values' => null,
                'new_values' => ['first_name' => 'Maria', 'last_name' => 'Santos'],
                'ip_address' => '127.0.0.1',
                'created_at' => Carbon::now()->subMinutes(15),
            ],
            [
                'user_id' => 1,
                'action' => 'created',
                'table_name' => 'users',
                'record_id' => 2,
                'old_values' => null,
                'new_values' => ['username' => 'nurse_jane'],
                'ip_address' => '127.0.0.1',
                'created_at' => Carbon::now()->subMinutes(5),
            ],
        ];

        foreach ($logs as $log) {
            AuditLog::create($log);
        }
    }
}