<?php

namespace App\Services;

use App\Enums\ConsultationStatus;
use App\Models\HealthWorker;
use App\Models\Patient;
use App\Models\Pregnancy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;

final class MaternalIntakeService
{
    /**
     * Record a maternal-program encounter, resolving (and creating when needed)
     * the consultation it belongs to. The maternal intake acts as the nurse
     * validation step: the consultation starts in the doctor queue, never the
     * nurse validation queue.
     */
    public function recordEncounter(
        Patient $patient,
        string $purpose,
        array $intake,
        HealthWorker $worker,
        ?Pregnancy $pregnancy = null,
        ?int $originConsultationId = null,
    ): int {
        return DB::transaction(function () use ($patient, $purpose, $intake, $worker, $pregnancy, $originConsultationId): int {
            $consultation = $this->resolveConsultation($patient, $originConsultationId);

            if ($consultation !== null) {
                return $this->attachToConsultation(
                    $consultation,
                    $purpose,
                    $intake,
                    $worker,
                    $pregnancy,
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

    /**
     * Prefer the explicitly selected origin consultation, falling back to the
     * patient's latest consultation from today, then to nothing.
     */
    private function resolveConsultation(Patient $patient, ?int $originConsultationId): ?stdClass
    {
        $query = DB::table('consultations')->where('patient_id', $patient->id);

        if ($originConsultationId !== null) {
            $query->where('id', $originConsultationId);
        } else {
            $query->whereDate('created_at', Carbon::today())->orderByDesc('created_at');
        }

        return $query->first();
    }

    private function attachToConsultation(
        stdClass $consultationRow,
        string $purpose,
        array $intake,
        HealthWorker $worker,
        ?Pregnancy $pregnancy,
    ): int {
        $updates = [];

        $existingPurpose = $consultationRow->purpose_of_visit ?? null;
        if ($existingPurpose === null || $existingPurpose === '') {
            $updates['purpose_of_visit'] = $purpose;
        }

        $existingPregnancyId = $consultationRow->pregnancy_id ?? null;
        if ($existingPregnancyId === null && $pregnancy !== null) {
            $updates['pregnancy_id'] = $pregnancy->id;
        }

        $existingEscalatedAt = $consultationRow->escalated_at ?? null;
        if ($existingEscalatedAt === null) {
            $vitals = VitalsService::fromInput($intake);
            $updates['escalated_at'] = MaternalRiskService::escalatedAt($vitals, $pregnancy);
        }

        if (in_array($consultationRow->status, [ConsultationStatus::Triage->value, ConsultationStatus::NurseReview->value], true)) {
            $updates['status'] = ConsultationStatus::DoctorReview->value;
            $updates['nurse_validated_at'] = now();
            $updates['nurse_validated_by'] = $worker->id;
        }

        if (! empty($updates)) {
            $updates['updated_at'] = now();

            DB::table('consultations')
                ->where('id', $consultationRow->id)
                ->update($updates);
        }

        $this->insertTriageVitals((int) $consultationRow->id, $intake, $worker);

        return (int) $consultationRow->id;
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
            'status' => ConsultationStatus::DoctorReview->value,
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

        $this->insertTriageVitals($consultationId, $intake, $worker);

        return $consultationId;
    }

    private function insertTriageVitals(int $consultationId, array $intake, HealthWorker $worker): void
    {
        DB::table('vitals')->insert(VitalsService::fromInput($intake) + [
            'consultation_id' => $consultationId,
            'phase' => 'triage',
            'captured_by' => $worker->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function activePregnancyFor(Patient $patient): ?Pregnancy
    {
        $pregnancy = $patient->pregnancies()
            ->where('status', Pregnancy::STATUS_ACTIVE)
            ->first();

        return $pregnancy instanceof Pregnancy ? $pregnancy : null;
    }
}
