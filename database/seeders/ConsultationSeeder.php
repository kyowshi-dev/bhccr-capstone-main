<?php

namespace Database\Seeders;

use App\Enums\ConsultationStatus;
use App\Models\HealthWorker;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConsultationSeeder extends Seeder
{
    public function run(): void
    {
        $patients = Patient::query()->orderBy('id')->get();
        $healthWorkers = HealthWorker::all();

        if ($patients->isEmpty() || $healthWorkers->isEmpty()) {
            $this->command?->warn('Please run PatientSeeder and ensure health workers exist before running ConsultationSeeder.');

            return;
        }

        $nurse = $healthWorkers->firstWhere('role', 'Nurse') ?? $healthWorkers->first();
        $doctor = $healthWorkers->firstWhere('role', 'Doctor') ?? $healthWorkers->first();
        $encoder = $healthWorkers->firstWhere('role', 'BHW') ?? $nurse;

        $chiefComplaints = [
            'Ubo ug sip-on',
            'Sakit sa tiyan',
            'Sakit sa ulo',
            'Hilanat',
            'Sakit sa lutahan',
            'Pasmo',
            'High blood',
            'Diabetes follow-up',
        ];
        $modeOfTransaction = ['Walk-in', 'Visited', 'Referral'];

        $consultations = [];
        $referredIndexes = [];

        // Exact status distribution across the whole seed set:
        // 1 nurse_review, 1 doctor_review, 1 referred, everything else completed.
        $firstConsultationStatuses = array_fill(0, $patients->count(), ConsultationStatus::Completed->value);
        $firstConsultationStatuses[0] = ConsultationStatus::NurseReview->value;
        $firstConsultationStatuses[1] = ConsultationStatus::DoctorReview->value;
        $firstConsultationStatuses[2] = ConsultationStatus::Referred->value;

        foreach ($patients as $index => $patient) {
            $statuses = [
                $firstConsultationStatuses[$index],
                ConsultationStatus::Completed->value,
            ];

            foreach ($statuses as $status) {
                if ($status === ConsultationStatus::Referred->value) {
                    $referredIndexes[] = count($consultations);
                }

                $consultations[] = $this->consultationPayload(
                    $patient,
                    $encoder,
                    $nurse,
                    $doctor,
                    $status,
                    $chiefComplaints,
                    $modeOfTransaction,
                );
            }
        }

        $this->insertConsultationsWithReferrals($consultations, $referredIndexes);

        $this->command?->info('Consultations seeded successfully: '.count($consultations).' records created.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function consultationPayload(
        Patient $patient,
        HealthWorker $encoder,
        HealthWorker $nurse,
        HealthWorker $doctor,
        string $status,
        array $chiefComplaints,
        array $modeOfTransaction,
    ): array {
        $createdAt = Carbon::now()->subDays(rand(1, 180));
        $nurseValidated = in_array($status, [
            ConsultationStatus::DoctorReview->value,
            ConsultationStatus::Completed->value,
            ConsultationStatus::Referred->value,
        ], true);

        $data = [
            'patient_id' => $patient->id,
            'status' => $status,
            'nurse_validated_at' => $nurseValidated ? $createdAt->copy()->addMinutes(rand(30, 240)) : null,
            'nurse_validated_by' => $nurseValidated ? $nurse->id : null,
            'attending_doctor_id' => in_array($status, [
                ConsultationStatus::Completed->value,
                ConsultationStatus::Referred->value,
            ], true) ? $doctor->id : null,
            'is_locked' => in_array($status, [
                ConsultationStatus::Completed->value,
                ConsultationStatus::Referred->value,
            ], true),
            'complaint_text' => $chiefComplaints[array_rand($chiefComplaints)],
            'nature_of_visit' => rand(0, 1) ? 'Follow-up' : 'Initial Consultation',
            'notes' => 'Patient presented with symptoms. Advised on home care and follow-up.',
            'mode_of_transaction' => $status === ConsultationStatus::Referred->value
                ? 'Referral'
                : $modeOfTransaction[array_rand($modeOfTransaction)],
            'created_at' => $createdAt,
            'updated_at' => $createdAt->copy()->addMinutes(rand(0, 60)),
        ];

        // The worker who handled the intake differs per stage of the workflow:
        // nurse handles consultations awaiting review, the BHW encoder handles the rest.
        $data['worker_id'] = in_array($status, [
            ConsultationStatus::NurseReview->value,
            ConsultationStatus::DoctorReview->value,
        ], true) ? $nurse->id : $encoder->id;

        if ($status === ConsultationStatus::Referred->value) {
            $data['referred_from'] = 'Barangay Health Center';
        }

        return $data;
    }

    /**
     * @param  list<array<string, mixed>>  $consultations
     * @param  list<int>  $referredIndexes  Flat consultation indexes that need an outward referral
     */
    private function insertConsultationsWithReferrals(array $consultations, array $referredIndexes): void
    {
        $referred = array_flip($referredIndexes);

        foreach ($consultations as $index => $row) {
            $consultationId = DB::table('consultations')->insertGetId($row);

            if (! isset($referred[$index])) {
                continue;
            }

            $createdAt = $row['created_at'];
            DB::table('outward_referrals')->insert([
                'consultation_id' => $consultationId,
                'destination_facility' => 'Tagoloan District Hospital',
                'pertinent_history' => $row['notes'] ?? $row['complaint_text'],
                'actions_taken' => 'Initial assessment and supportive care given.',
                'specific_details' => 'Referred for specialist consultation.',
                'status' => 'pending',
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
