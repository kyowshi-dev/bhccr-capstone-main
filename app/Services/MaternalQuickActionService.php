<?php

namespace App\Services;

use App\Enums\ConsultationStatus;
use App\Models\FamilyPlanningClient;
use App\Models\FamilyPlanningVisit;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Pregnancy;
use App\Models\PrenatalVisit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class MaternalQuickActionService
{
    public function __construct(private readonly PregnancyService $pregnancyService) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string}
     */
    public function execute(string $action, Patient $patient, array $data, int $workerId): array
    {
        return match ($action) {
            'register' => $this->registerLight($patient, $data, $workerId),
            'log_prenatal_visit' => $this->logPrenatalVisit($patient, $data, $workerId),
            'log_postpartum' => $this->logPostpartum($patient, $data, $workerId),
            'log_fp_visit' => $this->logFamilyPlanningVisit($patient, $data, $workerId),
            default => throw new \InvalidArgumentException("Unknown maternal quick action: {$action}"),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string}
     */
    private function registerLight(Patient $patient, array $data, int $workerId): array
    {
        $pregnancy = $this->pregnancyService->register($patient, [
            'lmp' => $data['lmp'],
            'risk_flags' => ! empty($data['risk_flags']) ? $data['risk_flags'] : null,
            'syphilis_result' => 'negative',
            'penicillin' => 'no',
        ], $workerId);

        return [
            'success' => true,
            'message' => 'Pregnancy registered. EDC '.($pregnancy->edc?->format('M d, Y') ?? '—').'.',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string}
     */
    private function logPrenatalVisit(Patient $patient, array $data, int $workerId): array
    {
        $pregnancy = $this->findActivePregnancy($patient);

        $consultationId = $this->createConsultation($patient, 'Prenatal', $data, $workerId);

        PrenatalVisit::create([
            'pregnancy_id' => $pregnancy->id,
            'consultation_id' => $consultationId,
            'visit_date' => $data['visit_date'],
            'fundic_height_cm' => $data['fundic_height_cm'] ?? null,
            'fetal_heart_tone_bpm' => $data['fetal_heart_tone_bpm'] ?? null,
            'next_visit_date' => $data['next_visit_date'] ?? null,
            'recorded_by' => $workerId,
        ]);

        return ['success' => true, 'message' => 'Prenatal visit logged.'];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string}
     */
    private function logPostpartum(Patient $patient, array $data, int $workerId): array
    {
        $record = PostnatalRecord::where('patient_id', $patient->id)
            ->orderByDesc('delivery_date')
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'action' => 'No postnatal record found for this patient.',
            ]);
        }

        $slotFilled = $this->fillOpenPostpartumSlot($record);

        $consultationId = $this->createConsultation($patient, 'Postpartum', $data, $workerId);

        if ($slotFilled) {
            $record->update(['consultation_id' => $consultationId]);
        }

        return ['success' => true, 'message' => $slotFilled.'.'];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string}
     */
    private function logFamilyPlanningVisit(Patient $patient, array $data, int $workerId): array
    {
        $client = FamilyPlanningClient::where('patient_id', $patient->id)
            ->where('is_active', true)
            ->first();

        if (! $client) {
            throw ValidationException::withMessages([
                'action' => 'No active family planning client found for this patient.',
            ]);
        }

        $consultationId = $this->createConsultation($patient, 'Family Planning', $data, $workerId);

        FamilyPlanningVisit::create([
            'client_id' => $client->id,
            'consultation_id' => $consultationId,
            'visit_date' => $data['visit_date'],
            'method' => $data['method'],
            'schedule_next_visit' => $data['next_visit_date'] ?? null,
            'recorded_by' => $workerId,
        ]);

        return ['success' => true, 'message' => 'Family planning visit logged.'];
    }

    private function findActivePregnancy(Patient $patient): Pregnancy
    {
        /** @var Pregnancy */
        $pregnancy = $patient->pregnancies()
            ->where('status', Pregnancy::STATUS_ACTIVE)
            ->first()
            ?: throw ValidationException::withMessages([
                'action' => 'No active pregnancy found. Register a pregnancy first.',
            ]);

        return $pregnancy;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createConsultation(Patient $patient, string $purpose, array $data, int $workerId): int
    {
        $consultationId = DB::table('consultations')->insertGetId([
            'patient_id' => $patient->id,
            'worker_id' => $workerId,
            'status' => ConsultationStatus::NurseReview->value,
            'nature_of_visit' => 'New Consultation/Case',
            'mode_of_transaction' => 'Walk-in',
            'purpose_of_visit' => $purpose,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vitalsPayload = VitalsService::fromInput([
            'bp_systolic' => $data['bp_systolic'] ?? null,
            'bp_diastolic' => $data['bp_diastolic'] ?? null,
            'weight' => $data['weight'] ?? null,
            'height' => $data['height'] ?? null,
            'temperature' => $data['temperature'] ?? null,
        ]) + [
            'consultation_id' => $consultationId,
            'phase' => 'triage',
            'captured_by' => $workerId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('vitals')->insert($vitalsPayload);

        return $consultationId;
    }

    private function fillOpenPostpartumSlot(PostnatalRecord $record): string
    {
        $today = Carbon::today();
        $slots = [
            'postpartum_24h_date' => [1, '24-hour follow-up'],
            'postpartum_7d_date' => [7, '7-day follow-up'],
            'postpartum_14d_date' => [14, '14-day follow-up'],
            'postpartum_28d_date' => [28, '28-day follow-up'],
        ];

        foreach ($slots as $column => [$days, $label]) {
            if ($record->{$column} !== null) {
                continue;
            }

            if (Carbon::parse($record->delivery_date)->addDays($days)->lte($today)) {
                $record->update([$column => $today->toDateString()]);

                return $label.' logged';
            }
        }

        throw ValidationException::withMessages([
            'action' => 'No open postpartum follow-up window for this patient.',
        ]);
    }
}
