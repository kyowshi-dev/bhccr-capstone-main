<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Pregnancy;
use Illuminate\Validation\ValidationException;

class PostnatalService
{
    public function __construct(
        private readonly PregnancyService $pregnancyService,
        private readonly PrenatalVisitService $prenatalVisitService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(Patient $mother, array $data, ?int $workerId): PostnatalRecord
    {
        $data['patient_id'] = $mother->id;
        $data['recorded_by'] = $workerId;
        $data['danger_signs_mother'] = $data['danger_signs_mother'] ?? [];
        $data['danger_signs_baby'] = $data['danger_signs_baby'] ?? [];

        $pregnancy = $this->resolvePregnancy($mother, $data);

        if ($pregnancy !== null) {
            $data['prenatal_visits_completed'] ??= $this->prenatalVisitService->countFor($pregnancy);
            $this->pregnancyService->markDelivered($pregnancy);
        }

        $record = PostnatalRecord::create($data);

        $this->syncChildPatient($record);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PostnatalRecord $record, array $data): PostnatalRecord
    {
        $data['danger_signs_mother'] = $data['danger_signs_mother'] ?? [];
        $data['danger_signs_baby'] = $data['danger_signs_baby'] ?? [];

        $pregnancy = $this->resolvePregnancy($record->patient, $data);

        if ($pregnancy !== null) {
            $data['prenatal_visits_completed'] ??= $this->prenatalVisitService->countFor($pregnancy);
            $this->pregnancyService->markDelivered($pregnancy);
        }

        if ($data['pregnancy_outcome'] !== PostnatalRecord::OUTCOME_LIVE_BIRTH) {
            foreach (['child_last_name', 'child_first_name', 'child_middle_name', 'child_sex', 'child_birth_length_cm', 'child_birth_weight_kg', 'child_patient_id'] as $field) {
                $data[$field] = null;
            }
        }

        $record->update($data);

        $this->syncChildPatient($record);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolvePregnancy(Patient $mother, array &$data): ?Pregnancy
    {
        $pregnancyId = $data['pregnancy_id'] ?? null;

        if ($pregnancyId === null || $pregnancyId === '') {
            $data['pregnancy_id'] = null;

            return null;
        }

        $pregnancy = Pregnancy::find($pregnancyId);

        if ($pregnancy === null || (int) $pregnancy->patient_id !== (int) $mother->id) {
            throw ValidationException::withMessages([
                'pregnancy_id' => 'The selected pregnancy does not belong to this patient.',
            ]);
        }

        return $pregnancy;
    }

    /**
     * Create or keep in sync the linked child `patients` record so the
     * newborn is immediately available to the immunization module.
     * Only live births produce a newborn record.
     */
    private function syncChildPatient(PostnatalRecord $record): void
    {
        if ($record->pregnancy_outcome !== PostnatalRecord::OUTCOME_LIVE_BIRTH) {
            return;
        }

        $child = $record->child_patient_id !== null
            ? Patient::find($record->child_patient_id)
            : null;

        $motherName = fullName(
            $record->patient->last_name,
            $record->patient->first_name,
            $record->patient->middle_name,
            $record->patient->suffix,
        );

        $attributes = [
            'first_name' => $record->child_first_name,
            'last_name' => $record->child_last_name,
            'middle_name' => $record->child_middle_name,
            'sex' => $record->child_sex === 'M' ? 'Male' : 'Female',
            'date_of_birth' => $record->delivery_date,
            'birth_weight' => $record->child_birth_weight_kg,
            'guardian_name' => $motherName,
            'mother_name' => $motherName,
            'family_relationship' => $record->child_sex === 'M' ? 'Son' : 'Daughter',
        ];

        if ($child !== null) {
            $child->update($attributes);

            return;
        }

        $this->assertNoDuplicateChild($record, $attributes);

        $child = Patient::create([
            'household_id' => $record->patient->household_id,
            'suffix' => null,
            'spouse_name' => '',
            'residential_address' => $record->patient->residential_address ?? '',
            'civil_status' => 'Single',
            ...$attributes,
        ]);

        $record->update(['child_patient_id' => $child->id]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function assertNoDuplicateChild(PostnatalRecord $record, array $attributes): void
    {
        $duplicate = Patient::where('household_id', $record->patient->household_id)
            ->where('first_name', $attributes['first_name'])
            ->where('last_name', $attributes['last_name'])
            ->whereDate('date_of_birth', $record->delivery_date)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'child_first_name' => 'A child with this name and birth date already exists in this household.',
            ]);
        }
    }
}
