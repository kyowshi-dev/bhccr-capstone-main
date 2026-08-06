<?php

namespace App\Services;

use App\Enums\ConsultationStatus;
use App\Models\Consultation;
use App\Models\HealthWorker;
use Illuminate\Support\Facades\DB;

final class VitalsService
{
    /**
     * Validation rules shared by all vitals entry points.
     *
     * @return array<string, list<string>>
     */
    public static function rules(bool $required = false): array
    {
        $presence = $required ? 'required' : 'nullable';

        return [
            'bp_systolic' => [$presence, 'numeric', 'min:0', 'max:300'],
            'bp_diastolic' => [$presence, 'numeric', 'min:0', 'max:200'],
            'temperature' => [$presence, 'numeric', 'min:30', 'max:45'],
            'weight' => [$presence, 'numeric', 'min:0', 'max:500'],
            'height' => [$presence, 'numeric', 'min:0', 'max:300'],
        ];
    }

    /**
     * Map the form-facing input keys onto the vitals table columns.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function fromInput(array $input): array
    {
        return [
            'bp_systolic' => $input['bp_systolic'] ?? null,
            'bp_diastolic' => $input['bp_diastolic'] ?? null,
            'weight_kg' => $input['weight'] ?? null,
            'height_cm' => $input['height'] ?? null,
            'temperature_c' => $input['temperature'] ?? null,
        ];
    }

    /**
     * Persist a clinical vitals version and move the consultation to in progress.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function recordClinical(Consultation $consultation, array $validated, HealthWorker $worker): void
    {
        $payload = self::fromInput($validated) + [
            'consultation_id' => $consultation->id,
            'phase' => 'clinical',
            'captured_by' => $worker->id,
            'notes' => $validated['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('vitals')->insert($payload);

        DB::table('consultations')
            ->where('id', $consultation->id)
            ->update(['status' => ConsultationStatus::InProgress->value, 'updated_at' => now()]);
    }

    /**
     * Update an existing vitals version belonging to the consultation.
     *
     * @param  array<string, mixed>  $validated
     */
    public static function updateVersion(Consultation $consultation, int $vitalId, array $validated): bool
    {
        $vital = DB::table('vitals')
            ->where('id', $vitalId)
            ->where('consultation_id', $consultation->id)
            ->first();

        if (! $vital) {
            return false;
        }

        $updatePayload = self::fromInput($validated) + [
            'notes' => $validated['notes'] ?? null,
            'updated_at' => now(),
        ];

        DB::table('vitals')
            ->where('id', $vitalId)
            ->where('consultation_id', $consultation->id)
            ->update($updatePayload);

        return true;
    }

    /**
     * Delete a vitals version, enforcing the baseline/triage deletion rules.
     */
    public static function deleteVersion(Consultation $consultation, int $vitalId): VitalsDeleteResult
    {
        $versions = DB::table('vitals')
            ->where('consultation_id', $consultation->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($versions->count() <= 1) {
            return new VitalsDeleteResult(error: 'Cannot delete the only vitals version.');
        }

        $vital = $versions->firstWhere('id', $vitalId);
        if (! $vital) {
            return new VitalsDeleteResult(notFound: true);
        }

        if (($vital->phase ?? null) === 'triage') {
            return new VitalsDeleteResult(error: 'Triage baseline vitals cannot be deleted.');
        }

        DB::table('vitals')
            ->where('id', $vitalId)
            ->where('consultation_id', $consultation->id)
            ->delete();

        return new VitalsDeleteResult(deleted: true);
    }
}
