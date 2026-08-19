<?php

namespace App\Services;

use App\Enums\ConsultationStatus;
use App\Models\Consultation;
use App\Models\HealthWorker;
use App\Models\Patient;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Consultation workflow operations (admission, validation, clinical records,
 * referral, finalization) and their shared domain rules.
 */
final class ConsultationService
{
    /**
     * Start a consultation: admission row, optional referral, triage vitals.
     *
     * @param  array<string, mixed>  $validated
     * @return array{consultationId: int, referralId: ?int}
     */
    public static function start(Patient $patient, array $validated, HealthWorker $worker): array
    {
        [$consultationId, $referralId] = DB::transaction(function () use ($validated, $patient, $worker): array {
            $consultationId = DB::table('consultations')->insertGetId([
                'patient_id' => $patient->id,
                'worker_id' => $worker->id,
                'status' => ConsultationStatus::NurseReview->value,
                'nature_of_visit' => $validated['nature_of_visit'],
                'mode_of_transaction' => $validated['mode_of_transaction'],
                'purpose_of_visit' => $validated['purpose_of_visit'] ?? null,
                'referred_from' => $validated['referred_from'] ?? null,
                'complaint_text' => $validated['chief_complaint'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $referralId = null;
            if (! empty($validated['refer_to_higher_facility'])) {
                $referral = ReferralService::upsert(Consultation::find($consultationId), $validated);

                $referralId = $referral->id;
            }

            $vitalsPayload = VitalsService::fromInput($validated) + [
                'consultation_id' => $consultationId,
                'phase' => 'triage',
                'captured_by' => $worker->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('vitals')->insert($vitalsPayload);

            return [$consultationId, $referralId];
        });

        return [
            'consultationId' => $consultationId,
            'referralId' => $referralId,
        ];
    }

    public static function acknowledgeIntake(Consultation $consultation, HealthWorker $worker): void
    {
        DB::table('consultations')->where('id', $consultation->id)->update([
            'status' => ConsultationStatus::DoctorReview->value,
            'nurse_validated_at' => now(),
            'nurse_validated_by' => $worker->id,
            'updated_at' => now(),
        ]);
    }

    public static function cancel(Consultation $consultation): void
    {
        DB::transaction(function () use ($consultation) {
            DB::table('vitals')->where('consultation_id', $consultation->id)->delete();
            DB::table('outward_referrals')->where('consultation_id', $consultation->id)->delete();
            DB::table('consultations')->where('id', $consultation->id)->delete();
        });
    }

    /**
     * Persist a diagnosis and return whether the consultation auto-completed.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function recordDiagnosis(Consultation $consultation, array $validated, HealthWorker $worker): bool
    {
        DB::table('diagnosis_records')->insert([
            'consultation_id' => $consultation->id,
            'diagnosis_id' => $validated['diagnosis_id'],
            'remarks' => $validated['remarks'] ?? null,
            'diagnosed_by' => $worker->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        self::promoteAttendingDoctor($consultation, $worker);

        if (self::maybeAutoComplete((int) $consultation->id)) {
            return true;
        }

        self::markInProgress($consultation);

        return false;
    }

    /**
     * Persist a prescription and return whether the consultation auto-completed.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function recordPrescription(Consultation $consultation, array $validated, HealthWorker $worker): bool
    {
        DB::table('prescriptions')->insert([
            'consultation_id' => $consultation->id,
            'medicine_id' => $validated['medicine_id'],
            'dosage' => $validated['dosage'],
            'frequency' => $validated['frequency'] ?? null,
            'duration' => $validated['duration'] ?? null,
            'quantity' => $validated['quantity'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        self::promoteAttendingDoctor($consultation, $worker);

        if (self::maybeAutoComplete((int) $consultation->id)) {
            return true;
        }

        self::markInProgress($consultation);

        return false;
    }

    /**
     * Submit a referral and mark the consultation as referred.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function refer(Consultation $consultation, array $validated): void
    {
        DB::transaction(function () use ($consultation, $validated) {
            ReferralService::upsert($consultation, $validated);

            DB::table('consultations')
                ->where('id', $consultation->id)
                ->update([
                    'status' => ConsultationStatus::Referred->value,
                    'updated_at' => now(),
                ]);
        });
    }

    /**
     * Finalize a consultation, optionally carrying a referral request.
     *
     * Returns the status the consultation ended with ('completed' or 'referred').
     *
     * @param  array<string, mixed>  $validated
     */
    public static function finalize(Consultation $consultation, array $validated, HealthWorker $worker): string
    {
        $requestedReferral = (bool) ($validated['refer_to_higher_facility'] ?? false);

        if ($requestedReferral && ! $worker->isClinical()) {
            throw new DomainException('Only Doctor or Nurse roles can trigger external referral.');
        }

        $status = ConsultationStatus::Completed->value;

        if ($requestedReferral) {
            ReferralService::upsert($consultation, $validated, keepExisting: true);
            $status = ConsultationStatus::Referred->value;
        }

        DB::table('consultations')
            ->where('id', $consultation->id)
            ->update(['status' => $status, 'updated_at' => now()]);

        self::promoteAttendingDoctor($consultation, $worker);

        return $status;
    }

    /**
     * Record the acting licensed worker as the attending doctor for the
     * consultation whenever clinical work is done by a Doctor or Nurse.
     * BHW/midwife-initiated intake never overwrites it.
     */
    private static function promoteAttendingDoctor(Consultation $consultation, HealthWorker $worker): void
    {
        if (! $worker->isClinical()) {
            return;
        }

        DB::table('consultations')
            ->where('id', $consultation->id)
            ->update([
                'attending_doctor_id' => $worker->id,
                'updated_at' => now(),
            ]);
    }

    public static function maybeAutoComplete(int $consultationId): bool
    {
        $consultation = DB::table('consultations')->where('id', $consultationId)->first();
        if (! $consultation || in_array($consultation->status, ConsultationStatus::terminalValues(), true)) {
            return false;
        }

        $hasDiagnosis = DB::table('diagnosis_records')->where('consultation_id', $consultationId)->exists();
        $hasPrescription = DB::table('prescriptions')->where('consultation_id', $consultationId)->exists();

        if (! $hasDiagnosis || ! $hasPrescription) {
            return false;
        }

        DB::table('consultations')->where('id', $consultationId)->update([
            'status' => ConsultationStatus::Completed->value,
            'updated_at' => now(),
        ]);

        return true;
    }

    public static function deleteDiagnosis(Consultation $consultation, int $diagnosisId): bool
    {
        $exists = DB::table('diagnosis_records')
            ->where('id', $diagnosisId)
            ->where('consultation_id', $consultation->id)
            ->exists();

        if (! $exists) {
            return false;
        }

        DB::table('diagnosis_records')->where('id', $diagnosisId)->delete();

        return true;
    }

    public static function deletePrescription(Consultation $consultation, int $prescriptionId): bool
    {
        $exists = DB::table('prescriptions')
            ->where('id', $prescriptionId)
            ->where('consultation_id', $consultation->id)
            ->exists();

        if (! $exists) {
            return false;
        }

        DB::table('prescriptions')->where('id', $prescriptionId)->delete();

        return true;
    }

    public static function updateNotes(Consultation $consultation, ?string $notes): void
    {
        DB::table('consultations')
            ->where('id', $consultation->id)
            ->update(['notes' => $notes, 'updated_at' => now()]);
    }

    public static function updateComplaint(Consultation $consultation, ?string $complaintText): void
    {
        DB::table('consultations')
            ->where('id', $consultation->id)
            ->update([
                'complaint_text' => $complaintText !== null ? trim($complaintText) : null,
                'updated_at' => now(),
            ]);
    }

    public static function hasDiagnosis(int $consultationId): bool
    {
        return DB::table('diagnosis_records')->where('consultation_id', $consultationId)->exists();
    }

    /**
     * Error message blocking clinical review, or null when the consultation
     * is open for doctor/nurse clinical actions.
     */
    public static function clinicalReviewError(Consultation $consultation): ?string
    {
        if (in_array($consultation->status, [ConsultationStatus::DoctorReview->value, ConsultationStatus::InProgress->value], true)) {
            return null;
        }

        return match ($consultation->status) {
            ConsultationStatus::NurseReview->value => 'Nurse intake validation must be completed before clinical review.',
            ConsultationStatus::Triage->value => 'Triage intake must be completed before clinical review.',
            default => 'This consultation is not open for clinical review.',
        };
    }

    private static function markInProgress(Consultation $consultation): void
    {
        DB::table('consultations')->where('id', $consultation->id)->update([
            'status' => ConsultationStatus::InProgress->value,
            'updated_at' => now(),
        ]);
    }
}
