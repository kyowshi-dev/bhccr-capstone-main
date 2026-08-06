<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Immunization;
use App\Models\ImmunizationStatusEvent;
use App\Models\Patient;
use App\Models\Vaccine;
use App\Models\VaccineSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ChildImmunizationService
{
    public const STATUS_COMPLETED = 'completed';

    public const STATUS_WAITING = 'waiting';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_OUT_OF_WINDOW = 'out_of_window';

    public const STATUS_NO_SHOW = 'no_show';

    public const QUEUE_DUE = 'due';

    public const DUE_WINDOW_DAYS = 7;

    /**
     * Per-request cache of each patient's immunization records, keyed by
     * patient id, so status computation does not re-query per (patient, vaccine).
     *
     * @var array<int, Collection<int, Immunization>>
     */
    private array $recordsCache = [];

    /**
     * Per-request cache of each patient's status events, keyed by patient id.
     *
     * @var array<int, Collection<int, ImmunizationStatusEvent>>
     */
    private array $eventsCache = [];

    /**
     * @return array{years: int, months: int, days: int}
     */
    public static function ageParts(Patient $patient): array
    {
        $diff = $patient->date_of_birth->diff(Carbon::now());

        return [
            'years' => $diff->y,
            'months' => $diff->m,
            'days' => $diff->d,
        ];
    }

    public function statusFor(Patient $patient, Vaccine $vaccine): string
    {
        $records = $this->recordsFor($patient, $vaccine);

        if ($this->unresolvedMissed($patient, $vaccine) !== null) {
            return self::STATUS_NO_SHOW;
        }

        $schedules = $vaccine->schedules;

        if ($schedules->isEmpty()) {
            return self::STATUS_WAITING;
        }

        $given = $records->where('no_show', false)->count();

        if ($vaccine->repeat_months === null && $given >= $schedules->count()) {
            return self::STATUS_COMPLETED;
        }

        $earliest = $this->nextDoseDate($patient, $vaccine);

        if ($earliest === null || Carbon::today()->lt($earliest)) {
            return self::STATUS_WAITING;
        }

        if ($this->isOutOfWindow($patient, $vaccine)) {
            return self::STATUS_OUT_OF_WINDOW;
        }

        return self::STATUS_OVERDUE;
    }

    /**
     * @return array{state: string, earliest_date: Carbon|null, requires_override: bool}
     */
    public function eligibility(Patient $patient, Vaccine $vaccine, ?int $doseNumber = null): array
    {
        $records = $this->recordsFor($patient, $vaccine);
        $schedules = $vaccine->schedules;

        if (
            $schedules->isEmpty()
            || ($vaccine->repeat_months === null && $records->where('no_show', false)->count() >= $schedules->count())
        ) {
            return ['state' => self::STATUS_COMPLETED, 'earliest_date' => null, 'requires_override' => false];
        }

        $nextIndex = $doseNumber !== null ? max(0, $doseNumber - 1) : $records->where('no_show', false)->count();
        $earliest = $this->nextDoseDate($patient, $vaccine, $nextIndex);

        if ($earliest !== null && Carbon::today()->lt($earliest)) {
            return ['state' => 'too_early', 'earliest_date' => $earliest, 'requires_override' => false];
        }

        if ($this->isOutOfWindow($patient, $vaccine)) {
            return ['state' => self::STATUS_OUT_OF_WINDOW, 'earliest_date' => $earliest, 'requires_override' => true];
        }

        return ['state' => 'overdue_allowed', 'earliest_date' => $earliest, 'requires_override' => false];
    }

    public function nextDose(Patient $patient, Vaccine $vaccine): ?VaccineSchedule
    {
        $nextIndex = $this->recordsFor($patient, $vaccine)->where('no_show', false)->count();

        return $vaccine->schedules->get($nextIndex) ?? ($vaccine->repeat_months !== null ? $vaccine->schedules->last() : null);
    }

    public function nextDoseNumber(Patient $patient, Vaccine $vaccine): int
    {
        return $this->recordsFor($patient, $vaccine)->where('no_show', false)->count() + 1;
    }

    /**
     * Earliest date the next dose may be given (birth-age rule vs gap since last dose).
     *
     * For repeat vaccines (e.g. annual influenza) the series never completes:
     * once the configured doses are exhausted the next dose is due repeat_months
     * after the last one given.
     */
    public function nextDoseDate(Patient $patient, Vaccine $vaccine, ?int $doseIndex = null): ?Carbon
    {
        $schedules = $vaccine->schedules;

        if ($schedules->isEmpty()) {
            return null;
        }

        $given = $this->recordsFor($patient, $vaccine)->where('no_show', false)->values();
        $nextIndex = $doseIndex ?? $given->count();
        $schedule = $schedules->get($nextIndex);

        if ($schedule === null) {
            if ($vaccine->repeat_months !== null && $given->isNotEmpty()) {
                return $given->last()->date_given->copy()->addMonths($vaccine->repeat_months);
            }

            return null;
        }

        $earliest = $patient->date_of_birth->copy()->addDays($schedule->min_age_days);

        if ($nextIndex > 0) {
            $lastGiven = $given->get($nextIndex - 1);
            $lastSchedule = $schedules->get($nextIndex - 1);

            if ($lastGiven !== null && $lastSchedule !== null && $lastSchedule->gap_days !== null) {
                $afterGap = $lastGiven->date_given->copy()->addDays($lastSchedule->gap_days);

                if ($afterGap->greaterThan($earliest)) {
                    $earliest = $afterGap;
                }
            }
        }

        return $earliest;
    }

    public function projectedCompletionDate(Patient $patient, Vaccine $vaccine): ?Carbon
    {
        $records = $this->recordsFor($patient, $vaccine);
        $schedules = $vaccine->schedules;

        if ($schedules->isEmpty()) {
            return null;
        }

        $nextIndex = $records->where('no_show', false)->count();
        $next = $this->nextDoseDate($patient, $vaccine, $nextIndex);

        if ($next === null) {
            return null;
        }

        $cursor = Carbon::today()->greaterThan($next) ? Carbon::today() : $next->copy();

        for ($i = $nextIndex; $i < $schedules->count() - 1; $i++) {
            $gap = $schedules->get($i)->gap_days;
            if ($gap !== null) {
                $cursor = $cursor->copy()->addDays($gap);
            }
        }

        return $cursor;
    }

    public function administer(Patient $patient, Vaccine $vaccine, array $data = []): Immunization
    {
        $records = $this->recordsFor($patient, $vaccine);
        $schedules = $vaccine->schedules;

        if ($schedules->isEmpty()) {
            throw ValidationException::withMessages([
                'vaccine_id' => 'This vaccine has no schedule configured.',
            ]);
        }

        $given = $records->where('no_show', false)->values();

        if ($vaccine->repeat_months === null && $given->count() >= $schedules->count()) {
            throw ValidationException::withMessages([
                'dose_number' => 'The series for this vaccine is already complete.',
            ]);
        }

        $nextIndex = $given->count();
        $doseNumber = $nextIndex + 1;
        $schedule = $schedules->get($nextIndex) ?? $schedules->last();
        $dateGiven = isset($data['date_given']) ? Carbon::parse($data['date_given']) : Carbon::today();

        $earliest = $this->nextDoseDate($patient, $vaccine, $nextIndex);

        if ($earliest !== null && $dateGiven->lt($earliest)) {
            throw ValidationException::withMessages([
                'date_given' => 'Too early for this dose. Earliest date: '.$earliest->format('M j, Y').'.',
            ]);
        }

        if ($this->isOutOfWindow($patient, $vaccine) && blank($data['override_reason'] ?? null)) {
            throw ValidationException::withMessages([
                'override_reason' => 'This vaccine is out of its age window; an override reason is required.',
            ]);
        }

        if ($schedule->requires_temp) {
            $temp = $data['temp_recorded'] ?? null;

            if ($temp === null || $temp === '') {
                throw ValidationException::withMessages([
                    'temp_recorded' => 'Temperature recording is required for this vaccine.',
                ]);
            }

            if (! is_numeric($temp) || (float) $temp < 30 || (float) $temp > 45) {
                throw ValidationException::withMessages([
                    'temp_recorded' => 'Temperature must be a number between 30 and 45.',
                ]);
            }
        }

        $this->assertNoGroupConflict($patient, $vaccine);

        $patient->immunizationRecords()
            ->where('vaccine_id', $vaccine->id)
            ->where('dose_number', $doseNumber)
            ->where('no_show', true)
            ->delete();

        $record = Immunization::create([
            'patient_id' => $patient->id,
            'vaccine_id' => $vaccine->id,
            'dose_number' => $doseNumber,
            'date_given' => $dateGiven,
            'temp_recorded' => $data['temp_recorded'] ?? null,
            'administered_by' => $data['administered_by'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->logEvent($patient, $vaccine, ImmunizationStatusEvent::TYPE_ATTENDED, $doseNumber, [
            'event_date' => $dateGiven->toDateString(),
        ]);

        $this->forgetRecords($patient);

        return $record;
    }

    /**
     * Record a missed appointment as an append-only MISSED event.
     *
     * No placeholder row is written to immunization_records: the event is the
     * permanent clinical record, and the patient's next dose slot is untouched.
     */
    public function markNoShow(Patient $patient, Vaccine $vaccine, ?int $doseNumber = null, array $data = []): ImmunizationStatusEvent
    {
        $records = $this->recordsFor($patient, $vaccine);
        $schedules = $vaccine->schedules;

        if ($schedules->isEmpty() || $records->where('no_show', false)->count() >= $schedules->count()) {
            throw ValidationException::withMessages([
                'dose_number' => 'The series for this vaccine is already complete.',
            ]);
        }

        return $this->logEvent($patient, $vaccine, ImmunizationStatusEvent::TYPE_MISSED, $doseNumber ?? ($records->where('no_show', false)->count() + 1), $data);
    }

    /**
     * Resolve an unresolved MISSED event with an append-only CLEARED event.
     */
    public function clearNoShow(Patient $patient, Vaccine $vaccine, array $data = []): ?ImmunizationStatusEvent
    {
        $missed = $this->unresolvedMissed($patient, $vaccine);

        if ($missed === null) {
            return null;
        }

        return $this->logEvent($patient, $vaccine, ImmunizationStatusEvent::TYPE_CLEARED, $missed->dose_number, $data);
    }

    /**
     * Latest MISSED event that has not been superseded by an ATTENDED or
     * CLEARED event (or a real dose record) dated on/after the miss.
     */
    public function unresolvedMissed(Patient $patient, Vaccine $vaccine): ?ImmunizationStatusEvent
    {
        $events = $this->statusEventsFor($patient)
            ->where('vaccine_id', $vaccine->id)
            ->sortByDesc('event_date')
            ->values();

        $missed = $events->first(fn (ImmunizationStatusEvent $event) => $event->event_type === ImmunizationStatusEvent::TYPE_MISSED);

        if ($missed === null) {
            return null;
        }

        $hasResolution = $events->contains(fn (ImmunizationStatusEvent $event) => in_array($event->event_type, [ImmunizationStatusEvent::TYPE_ATTENDED, ImmunizationStatusEvent::TYPE_CLEARED], true) && $event->event_date->gte($missed->event_date));

        if ($hasResolution) {
            return null;
        }

        $hasDoseAfterMiss = $this->recordsFor($patient, $vaccine)
            ->where('no_show', false)
            ->where('date_given', '>=', $missed->event_date)
            ->isNotEmpty();

        return $hasDoseAfterMiss ? null : $missed;
    }

    /**
     * Whether a vaccine's category matches the patient's age group.
     */
    public function vaccineMatchesAge(Patient $patient, Vaccine $vaccine): bool
    {
        $isChild = ($patient->age ?? 0) < 18;
        $allowed = $isChild ? ['Child', 'Both'] : ['Adult', 'Both'];

        return in_array($vaccine->category, $allowed, true);
    }

    /**
     * Enroll a new infant, creating the household when none is supplied.
     *
     * @param  array<string, mixed>  $data
     */
    public function enrollInfant(array $data): Patient
    {
        $householdId = $data['household_id'] ?? null;

        if ($householdId === null) {
            $household = Household::create([
                'zone_id' => $data['zone_id'],
                'family_name_head' => $data['family_name_head'],
                'contact_number' => $data['contact_number'] ?? null,
            ]);
            $householdId = $household->id;
        }

        $duplicate = Patient::where('household_id', $householdId)
            ->where('first_name', $data['first_name'])
            ->where('last_name', $data['last_name'])
            ->whereDate('date_of_birth', $data['date_of_birth'])
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'duplicate' => 'An infant with this name and birth date already exists in this household.',
            ]);
        }

        $relationship = $data['sex'] === 'Female' ? 'Daughter' : 'Son';

        return Patient::create([
            'household_id' => $householdId,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'suffix' => $data['suffix'] ?? null,
            'sex' => $data['sex'],
            'date_of_birth' => $data['date_of_birth'],
            'birth_weight' => $data['birth_weight'] ?? null,
            'guardian_name' => $data['guardian_name'] ?? null,
            'mother_name' => $data['mother_name'] ?? $data['guardian_name'] ?? '',
            'spouse_name' => '',
            'family_relationship' => $relationship,
            'residential_address' => $this->zoneAddress($householdId),
            'civil_status' => 'Single',
        ]);
    }

    /**
     * Index queues: due / overdue / out_of_window / no_show.
     *
     * The "due" queue uses a rolling window (DUE_WINDOW_DAYS from the target
     * date) so children who became eligible between session days are not
     * missed; everything earlier than the target is "overdue".
     *
     * @param  list<string>  $categories
     * @return Collection<int, array{patient: Patient, vaccine: Vaccine, status: string, dose_number: int, due_date: Carbon|null}>
     */
    public function queue(string $mode, ?int $zoneId = null, ?string $date = null, array $categories = ['Child', 'Both']): Collection
    {
        $query = Patient::query()->whereHas('household');

        if ($zoneId !== null) {
            $query->whereHas('household', fn ($q) => $q->where('zone_id', $zoneId));
        }

        $patients = $query->with(['immunizationRecords', 'immunizationStatusEvents', 'household.zone'])->get();

        $vaccines = Vaccine::whereIn('category', $categories)->with('schedules')->get();

        $target = $date !== null ? Carbon::parse($date) : Carbon::today();

        $childFocused = in_array('Child', $categories, true);

        $entries = [];

        foreach ($patients as $patient) {
            $age = $patient->age;

            if ($age === null || ($childFocused && $age >= 18) || (! $childFocused && $age < 18)) {
                continue;
            }

            foreach ($vaccines as $vaccine) {
                $earliest = $this->nextDoseDate($patient, $vaccine);

                if ($earliest === null) {
                    continue;
                }

                $status = $this->statusFor($patient, $vaccine);

                if ($mode === self::QUEUE_DUE) {
                    if (in_array($status, [self::STATUS_NO_SHOW, self::STATUS_OUT_OF_WINDOW, self::STATUS_COMPLETED], true)) {
                        continue;
                    }

                    if ($earliest->lt($target) || $earliest->gt($target->copy()->addDays(self::DUE_WINDOW_DAYS))) {
                        continue;
                    }

                    $entries[] = [
                        'patient' => $patient,
                        'vaccine' => $vaccine,
                        'status' => self::QUEUE_DUE,
                        'dose_number' => $this->nextDoseNumber($patient, $vaccine),
                        'due_date' => $earliest,
                    ];

                    continue;
                }

                if ($status !== $mode || ($mode === self::STATUS_OVERDUE && ! $earliest->lt($target))) {
                    continue;
                }

                $entries[] = [
                    'patient' => $patient,
                    'vaccine' => $vaccine,
                    'status' => $status,
                    'dose_number' => $this->nextDoseNumber($patient, $vaccine),
                    'due_date' => $earliest,
                ];
            }
        }

        return new Collection($entries);
    }

    private function assertNoGroupConflict(Patient $patient, Vaccine $vaccine): void
    {
        if ($vaccine->group_key === null) {
            return;
        }

        $conflictingIds = Vaccine::where('group_key', $vaccine->group_key)
            ->whereKeyNot($vaccine->id)
            ->pluck('id');

        if ($conflictingIds->isEmpty()) {
            return;
        }

        $conflict = $patient->immunizationRecords()
            ->whereIn('vaccine_id', $conflictingIds)
            ->where('no_show', false)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'vaccine_id' => 'This patient has already received another vaccine in the same group ('.$vaccine->group_key.').',
            ]);
        }
    }

    private function isOutOfWindow(Patient $patient, Vaccine $vaccine): bool
    {
        if ($vaccine->complete_before_days === null) {
            return false;
        }

        if (($vaccine->start_after_days ?? 0) <= 0) {
            return false;
        }

        $projected = $this->projectedCompletionDate($patient, $vaccine);

        return $projected !== null
            && $projected->greaterThan($patient->date_of_birth->copy()->addDays($vaccine->complete_before_days));
    }

    /**
     * @return Collection<int, Immunization>
     */
    private function recordsFor(Patient $patient, Vaccine $vaccine): Collection
    {
        return $this->patientRecords($patient)
            ->where('vaccine_id', $vaccine->id)
            ->sortBy('dose_number')
            ->values();
    }

    /**
     * All of a patient's immunization records, fetched once per request.
     *
     * @return Collection<int, Immunization>
     */
    private function patientRecords(Patient $patient): Collection
    {
        return $this->recordsCache[$patient->id] ??= $patient->immunizationRecords;
    }

    /**
     * All of a patient's status events, fetched once per request.
     *
     * @return Collection<int, ImmunizationStatusEvent>
     */
    private function statusEventsFor(Patient $patient): Collection
    {
        return $this->eventsCache[$patient->id] ??= $patient->immunizationStatusEvents->sortBy('event_date')->values();
    }

    private function logEvent(Patient $patient, Vaccine $vaccine, string $type, ?int $doseNumber, array $data = []): ImmunizationStatusEvent
    {
        $event = ImmunizationStatusEvent::create([
            'patient_id' => $patient->id,
            'vaccine_id' => $vaccine->id,
            'dose_number' => $doseNumber,
            'event_type' => $type,
            'event_date' => isset($data['event_date']) ? Carbon::parse($data['event_date']) : Carbon::today(),
            'note' => $data['note'] ?? null,
            'user_id' => auth()->id() ?: null,
        ]);

        $this->forgetEvents($patient);

        return $event;
    }

    private function forgetRecords(Patient $patient): void
    {
        unset($this->recordsCache[$patient->id]);
        $patient->unsetRelation('immunizationRecords');
    }

    private function forgetEvents(Patient $patient): void
    {
        unset($this->eventsCache[$patient->id]);
        $patient->unsetRelation('immunizationStatusEvents');
    }

    private function zoneAddress(int $householdId): string
    {
        $zone = Household::with('zone')->find($householdId)?->zone;

        return $zone !== null ? 'Purok '.$zone->zone_number.', Barangay Sta. Ana' : '';
    }
}
