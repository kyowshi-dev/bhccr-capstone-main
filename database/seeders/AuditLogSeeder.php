<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('username', 'admin')->first();
        if ($admin === null) {
            $this->command?->warn('No admin user found; skipping AuditLogSeeder.');

            return;
        }

        $logs = [];

        $logs[] = [
            'user_id' => $admin->id,
            'action' => 'created',
            'table_name' => 'users',
            'record_id' => $admin->id,
            'old_values' => null,
            'new_values' => ['username' => $admin->username],
            'ip_address' => '127.0.0.1',
            'created_at' => Carbon::now()->subHours(2),
        ];

        foreach (Patient::limit(2)->get() as $patient) {
            $logs[] = [
                'user_id' => $admin->id,
                'action' => 'created',
                'table_name' => 'patients',
                'record_id' => $patient->id,
                'old_values' => null,
                'new_values' => ['first_name' => $patient->first_name, 'last_name' => $patient->last_name],
                'ip_address' => '127.0.0.1',
                'created_at' => Carbon::now()->subHours(1)->subMinutes($patient->id),
            ];
        }

        foreach (Consultation::limit(2)->get() as $consultation) {
            $logs[] = [
                'user_id' => $admin->id,
                'action' => 'updated',
                'table_name' => 'consultations',
                'record_id' => $consultation->id,
                'old_values' => ['status' => 'triage'],
                'new_values' => ['status' => $consultation->status],
                'ip_address' => '127.0.0.1',
                'created_at' => Carbon::now()->subMinutes(15)->subMinutes($consultation->id),
            ];
        }

        foreach ($logs as $log) {
            AuditLog::create($log);
        }

        $this->command?->info('Audit logs seeded: '.count($logs).' records created.');
    }
}
