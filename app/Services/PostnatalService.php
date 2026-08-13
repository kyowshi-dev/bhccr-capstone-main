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

        $this->prepare($mother, $data);
        $this->assertNoDuplicateNewborn($data, $mother->household_id);

        $record = PostnatalRecord::create($data);

        $this->syncChildPatient($record);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PostnatalRecord $record, array $data): PostnatalRecord
    {
        $this->prepare($record->patient, $data);

        if ($data['pregnancy_outcome'] !== PostnatalRecord::OUTCOME_LIVE_BIRTH) {
            foreach (PostnatalRecord::NEWBORN_FIELDS as $field) {
                $data[$field] = null;
            }
        }

        $this->assertNoDuplicateNewborn($data, $record->patient->household_id, $record->child_patient_id);

        $record->update($data);

        $this->syncChildPatient($record);

        return $record;
    }

    /**
     * Normalize input and resolve the linked pregnancy, filling derived
     * fields and advancing the pregnancy to the delivered state.
     *
     * @param  array<string, mixed>  $data
     */
    private function prepare(Patient $mother, array &$data): void
    {
        $data['danger_signs_mother'] = $data['danger_signs_mother'] ?? [];
        $data['danger_signs_baby'] = $data['danger_signs_baby'] ?? [];

        $pregnancy = $this->resolvePregnancy($mother, $data);

        if ($pregnancy === null) {
            return;
        }

        $data['prenatal_visits_completed'] ??= $this->prenatalVisitService->countFor($pregnancy);
        $this->pregnancyService->markDelivered($pregnancy);
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
     * Guard against enrolling a newborn that already exists in the household.
     * Runs before the record is persisted so a rejected save leaves no data.
     *
     * @param  array<string, mixed>  $data
     */
    private function assertNoDuplicateNewborn(array $data, ?int $householdId, ?int $excludePatientId = null): void
    {
        $lastName = $data['child_last_name'] ?? null;
        $firstName = $data['child_first_name'] ?? null;
        $deliveryDate = $data['delivery_date'] ?? null;

        if ($data['pregnancy_outcome'] !== PostnatalRecord::OUTCOME_LIVE_BIRTH
            || $lastName === null || $firstName === null || $deliveryDate === null || $householdId === null) {
            return;
        }

        $duplicate = Patient::where('household_id', $householdId)
            ->where('first_name', $firstName)
            ->where('last_name', $lastName)
            ->whereDate('date_of_birth', $deliveryDate);

        if ($excludePatientId !== null) {
            $duplicate->where('id', '!=', $excludePatientId);
        }

        if ($duplicate->exists()) {
            throw ValidationException::withMessages([
                'child_first_name' => 'A child with this name and birth date already exists in this household.',
            ]);
        }
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

        if ($child !== null) {
            $child->update($this->newbornAttributes($record));

            return;
        }

        $child = Patient::create([
            'household_id' => $record->patient->household_id,
            'suffix' => null,
            'spouse_name' => '',
            'residential_address' => $record->patient->residential_address ?? '',
            'civil_status' => 'Single',
            ...$this->newbornAttributes($record),
        ]);

        $record->update(['child_patient_id' => $child->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function newbornAttributes(PostnatalRecord $record): array
    {
        $motherName = fullName(
            $record->patient->last_name,
            $record->patient->first_name,
            $record->patient->middle_name,
            $record->patient->suffix,
        );

        $isMale = $record->child_sex === 'M';

        return [
            'first_name' => $record->child_first_name,
            'last_name' => $record->child_last_name,
            'middle_name' => $record->child_middle_name,
            'sex' => $isMale ? 'Male' : 'Female',
            'date_of_birth' => $record->delivery_date,
            'birth_weight' => $record->child_birth_weight_kg,
            'guardian_name' => $motherName,
            'mother_name' => $motherName,
            'family_relationship' => $isMale ? 'Son' : 'Daughter',
        ];
    }
}
