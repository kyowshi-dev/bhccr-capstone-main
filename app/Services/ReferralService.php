<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\OutwardReferral;
use Illuminate\Support\Facades\DB;

final class ReferralService
{
    /**
     * Human-readable labels for structured referral reason keys.
     *
     * @var array<string, string>
     */
    public const REASON_LABELS = [
        'specialized_evaluation' => 'Need for specialized medical evaluation / physician',
        'lack_diagnostics' => 'Lack of diagnostic equipment / laboratory tests',
        'lack_medicines' => 'Lack of available medicines / vaccines',
        'emergency_trauma' => 'Emergency / trauma stabilization required',
    ];

    /**
     * Build the free-text "specific details" block from structured reasons
     * plus an optional detail note, or null when nothing was provided.
     *
     * @param  list<string>  $reasons
     */
    public static function specificDetails(array $reasons, ?string $details): ?string
    {
        $labels = array_filter(array_map(
            fn (string $reason): string => self::REASON_LABELS[$reason] ?? $reason,
            $reasons
        ));

        $reasonText = $labels ? 'Reasons: '.implode(', ', $labels) : '';
        $details = trim((string) $details);
        $specificDetails = trim($reasonText.($details ? "\n\n".$details : ''));

        return $specificDetails ?: null;
    }

    /**
     * Insert or update the outward referral attached to a consultation.
     *
     * The outward_referrals table has a unique constraint on consultation_id,
     * so this upserts. When $keepExisting is true an existing referral row is
     * left untouched instead of being overwritten with the latest submission.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function upsert(Consultation $consultation, array $validated, bool $keepExisting = false): OutwardReferral
    {
        $existing = $consultation->outwardReferral;

        $payload = [
            'destination_facility' => $validated['referred_to'],
            'pertinent_history' => $validated['pertinent_history'],
            'actions_taken' => $validated['actions_taken'] ?? null,
            'specific_details' => self::specificDetails(
                $validated['referral_reasons'] ?? [],
                $validated['referral_reason_details'] ?? null
            ),
        ];

        if ($existing && $keepExisting) {
            unset($payload['destination_facility'], $payload['pertinent_history']);

            $existing->update($payload + ['updated_at' => now()]);

            return $existing;
        }

        if ($existing) {
            $existing->update($payload + ['status' => OutwardReferral::STATUS_PENDING, 'updated_at' => now()]);

            return $existing;
        }

        $referral = OutwardReferral::create([
            'consultation_id' => $consultation->id,
            'destination_facility' => $payload['destination_facility'],
            'pertinent_history' => $payload['pertinent_history'],
            'actions_taken' => $payload['actions_taken'],
            'specific_details' => $payload['specific_details'],
            'status' => OutwardReferral::STATUS_PENDING,
        ]);

        self::notifyReferralCreated($consultation, $referral);

        return $referral;
    }

    /**
     * Referral confirmation context for the JS referral wizard.
     *
     * @return array{patient_name: string, patient_meta: string, vitals_summary: string}
     */
    public static function context(Consultation $consultation): array
    {
        $patient = $consultation->patient;

        $metaParts = [];

        if (! empty($patient->age)) {
            $metaParts[] = $patient->age.' y/o';
        }

        if (! empty($patient->sex)) {
            $metaParts[] = ucfirst($patient->sex);
        }

        $name = trim(fullName($patient->last_name ?? null, $patient->first_name ?? null, $patient->middle_name ?? null, $patient->suffix ?? null));

        $summary = DB::table('vitals')
            ->where('consultation_id', $consultation->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $vitalsSummary = '';

        if ($summary) {
            $parts = [];

            if ($summary->bp_systolic !== null || $summary->bp_diastolic !== null) {
                $parts[] = 'BP '.($summary->bp_systolic ?? '-').'/'.($summary->bp_diastolic ?? '-').' mmHg';
            }

            if ($summary->temperature_c !== null) {
                $parts[] = 'Temp '.$summary->temperature_c.'°C';
            }

            if ($summary->weight_kg !== null) {
                $parts[] = 'Weight '.$summary->weight_kg.' kg';
            }

            if ($summary->height_cm !== null) {
                $parts[] = 'Height '.$summary->height_cm.' cm';
            }

            $vitalsSummary = implode(' · ', $parts);
        }

        return [
            'patient_name' => trim($name) ?: '-',
            'patient_meta' => $metaParts ? implode(' · ', $metaParts) : '-',
            'vitals_summary' => $vitalsSummary ?: '-',
        ];
    }

    public static function updateStatus(int $id, string $status): bool
    {
        return (bool) DB::table('outward_referrals')
            ->where('id', $id)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);
    }

    /**
     * Alert consultation staff when a pending referral row is created.
     */
    private static function notifyReferralCreated(Consultation $consultation, OutwardReferral $referral): void
    {
        $patient = $consultation->patient;
        $name = trim(fullName($patient->last_name ?? null, $patient->first_name ?? null, $patient->middle_name ?? null, $patient->suffix ?? null));
        $patientLabel = $name !== '' ? $name : 'Patient #'.$patient->id;

        NotificationService::sendToPermissionHolders(
            'consultations',
            'referral_created',
            'Referral created - '.$patientLabel.' → '.$referral->destination_facility,
            'A new pending referral to '.$referral->destination_facility.' was created.',
            route('consultations.show', $consultation->id),
            patientIds: [$consultation->patient_id],
            excludeUserId: auth()->id(),
        );
    }
}
