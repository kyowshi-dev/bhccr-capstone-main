<?php

namespace App\Services;

use App\Enums\ConsultationStatus;
use App\Models\HealthWorker;
use App\Models\Patient;
use App\Models\Pregnancy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class MaternalIntakeService
{
    public function recordEncounter(
        Patient $patient,
        string $purpose,
        array $intake,
        HealthWorker $worker,
        ?Pregnancy $pregnancy = null,
    ): int {
        return DB::transaction(function () use ($patient, $purpose, $intake, $worker, $pregnancy): int {
            $today = Carbon::today();

            $existingConsultation = DB::table('consultations')
                ->where('patient_id', $patient->id)
                ->whereDate('created_at', $today)
                ->orderByDesc('created_at')
                ->first();

            if ($existingConsultation) {
                return $this->attachToConsultation(
                    (int) $existingConsultation->id,
                    $purpose,
                    $intake,
                    $worker,
                    $pregnancy,
                    $existingConsultation,
                );
            }

            return $this->createConsultation(
                $patient,
                $purpose,
                $intake,
                $worker,
                $pregnancy,
            );
        });
    }

    private function attachToConsultation(
        int $consultationId,
        string $purpose,
        array $intake,
        HealthWorker $worker,
        ?Pregnancy $pregnancy,
        \stdClass $existingRow,
    ): int {
        $updates = [];

        $existingPurpose = $existingRow->purpose_of_visit ?? null;
        if ($existingPurpose === null || $existingPurpose === '') {
            $updates['purpose_of_visit'] = $purpose;
        }

        $existingPregnancyId = $existingRow->pregnancy_id ?? null;
        if ($existingPregnancyId === null && $pregnancy !== null) {
            $updates['pregnancy_id'] = $pregnancy->id;
        }

        $existingEscalatedAt = $existingRow->escalated_at ?? null;
        if ($existingEscalatedAt === null) {
            $vitals = VitalsService::fromInput($intake);
            $updates['escalated_at'] = MaternalRiskService::escalatedAt($vitals, $pregnancy);
        }

        if (! empty($updates)) {
            $updates['updated_at'] = now();

            DB::table('consultations')
                ->where('id', $consultationId)
                ->update($updates);
        }

        $vitalsPayload = VitalsService::fromInput($intake) + [
            'consultation_id' => $consultationId,
            'phase' => 'triage',
            'captured_by' => $worker->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('vitals')->insert($vitalsPayload);

        $this->clearCaches();

        return $consultationId;
    }

    private function createConsultation(
        Patient $patient,
        string $purpose,
        array $intake,
        HealthWorker $worker,
        ?Pregnancy $pregnancy,
    ): int {
        $vitalsForRisk = VitalsService::fromInput($intake);

        $consultationId = DB::table('consultations')->insertGetId([
            'patient_id' => $patient->id,
            'worker_id' => $worker->id,
            'status' => ConsultationStatus::NurseReview->value,
            'nature_of_visit' => $intake['nature_of_visit'] ?? 'New Consultation/Case',
            'mode_of_transaction' => $intake['mode_of_transaction'] ?? 'Walk-in',
            'purpose_of_visit' => $purpose,
            'pregnancy_id' => $pregnancy?->id,
            'complaint_text' => $intake['chief_complaint'] ?? null,
            'escalated_at' => MaternalRiskService::escalatedAt($vitalsForRisk, $pregnancy),
            'nurse_validated_at' => now(),
            'nurse_validated_by' => $worker->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vitalsPayload = $vitalsForRisk + [
            'consultation_id' => $consultationId,
            'phase' => 'triage',
            'captured_by' => $worker->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('vitals')->insert($vitalsPayload);

        $this->clearCaches();

        return $consultationId;
    }

    private function clearCaches(): void
    {
        Cache::forget('maternal_queue_aggregate');
        Cache::forget('maternal_queue_kpis');
    }

    public function activePregnancyFor(Patient $patient): ?Pregnancy
    {
        $pregnancy = $patient->pregnancies()
            ->where('status', Pregnancy::STATUS_ACTIVE)
            ->first();

        return $pregnancy instanceof Pregnancy ? $pregnancy : null;
    }
}
