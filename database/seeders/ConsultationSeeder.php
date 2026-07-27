<?php

namespace Database\Seeders;

use App\Models\Consultation;
use App\Models\Patient;
use App\Models\HealthWorker;
use App\Models\ComplaintLookup;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ConsultationSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = ['triage', 'pending_doctor', 'in_progress', 'completed', 'referred'];
        $modeOfTransaction = ['Walk-in', 'Referral', 'Scheduled', 'Emergency'];
        $chiefComplaints = [
            'Cough and Cold',
            'Fever',
            'Headache',
            'Stomach Pain',
            'Dizziness',
            'High Blood Pressure',
            'Wound/Injury',
            'Prenatal Checkup',
            'Immunization',
        ];

        // Get all patients and health workers
        $patients = Patient::all();
        $healthWorkers = HealthWorker::all();

        if ($patients->isEmpty() || $healthWorkers->isEmpty()) {
            $this->command->warn('Please run PatientSeeder and ensure health workers exist before running ConsultationSeeder.');
            return;
        }

        $consultations = [];

        // Create 2-3 consultations per patient
        foreach ($patients as $patient) {
            $consultationCount = rand(1, 3);

            for ($i = 0; $i < $consultationCount; $i++) {
                $status = $statuses[array_rand($statuses)];
                $complaint = $chiefComplaints[array_rand($chiefComplaints)];
                $worker = $healthWorkers->random();

                // Get chief complaint ID
                $chiefComplaintId = DB::table('complaint_lookup')
                    ->where('complaint', $complaint)
                    ->value('id');

                $consultationData = [
                    'patient_id' => $patient->id,
                    'worker_id' => $worker->id,
                    'status' => $status,
                    'is_locked' => $status === 'completed' ? rand(0, 1) : false,
                    'chief_complaint_id' => $chiefComplaintId,
                    'complaint_text' => $complaint,
                    'nature_of_visit' => rand(0, 1) ? 'Follow-up' : 'Initial Consultation',
                    'created_at' => Carbon::now()->subDays(rand(1, 180)),
                    'updated_at' => Carbon::now()->subDays(rand(0, 180)),
                ];

                // Add optional fields only if they exist in the database
                if (Schema::hasColumn('consultations', 'notes')) {
                    $consultationData['notes'] = rand(0, 1) ? 'Patient presented with symptoms. Advised to return for follow-up.' : null;
                }

                if (Schema::hasColumn('consultations', 'mode_of_transaction')) {
                    $consultationData['mode_of_transaction'] = $modeOfTransaction[array_rand($modeOfTransaction)];
                }

                if (Schema::hasColumn('consultations', 'referred_from')) {
                    $consultationData['referred_from'] = $consultationData['mode_of_transaction'] === 'Referral' ? 'Barangay Health Center' : null;
                }

                if (Schema::hasColumn('consultations', 'refer_to_higher_facility')) {
                    $consultationData['refer_to_higher_facility'] = $status === 'referred' ? rand(0, 1) : false;
                }

                if (Schema::hasColumn('consultations', 'referred_to')) {
                    $consultationData['referred_to'] = $status === 'referred' ? 'County Hospital' : null;
                }

                if (Schema::hasColumn('consultations', 'referral_reason')) {
                    $consultationData['referral_reason'] = $status === 'referred' ? 'Need for specialist consultation' : null;
                }

                $consultations[] = $consultationData;
            }
        }

        // Insert in chunks to avoid memory issues
        $chunks = array_chunk($consultations, 500);
        foreach ($chunks as $chunk) {
            DB::table('consultations')->insert($chunk);
        }

        $this->command->info('Consultations seeded successfully: ' . count($consultations) . ' records created.');
    }
}
