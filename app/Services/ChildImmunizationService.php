<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Immunization;
use App\Models\ImmunizationStatusEvent;
use App\Models\Patient;
use App\Models\Vaccine;
use App\Models\VaccineSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class ChildImmunizationService
{
    public const string STATUS_COMPLETED = 'completed';

    public const string STATUS_WAITING = 'waiting';

    public const string STATUS_OVERDUE = 'overdue';

    public const string STATUS_OUT_OF_WINDOW = 'out_of_window';

    public const string STATUS_NO_SHOW = 'no_show';

    public const string QUEUE_DUE = 'due';

    public const int DUE_WINDOW_DAYS = 7;

    public const int OVERDUE_LOOKBACK_DAYS = 730;

    /**
     * How long computed queue results stay fresh. Computing the queues is
     * O(enrolled patients x vaccines) in PHP, which is expensive on large
     * registries; a short TTL with write-time invalidation keeps the index
     * page fast without ever showing stale clinical data.
     */
    public const int QUEUE_CACHE_TTL = 600;

    public const string QUEUE_GENERATION_KEY = 'immunization_queue_generation';

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
     * Per-request cache of vaccine ids grouped by group_key.
     *
     * @var array<string, Collection<int, int>>
     */
    private array $groupMemberCache = [];

    /**
     * Per-request cache of a patient's records filtered to one vaccine,
     * keyed by patient id then vaccine id.
     *
     * @var array<int, array<int, Collection<int, Immunization>>>
     */
    private array $recordsByVaccineCache = [];

    /**
     * Per-request cache of a patient's real (non-no-show) records grouped by
     * vaccine id, so the "given" count never re-scans the records collection.
     * Keyed by patient id then vaccine id.
     *
     * @var array<int, array<int, Collection<int, Immunization>>>
     */
    private array $givenByVaccineCache = [];

    /**
     * Per-request cache of a patient's status events filtered to one vaccine,
     * sorted by event date descending. Keyed by patient id then vaccine id.
     *
     * @var array<int, array<int, Collection<int, ImmunizationStatusEvent>>>
     */
    private array $eventsByVaccineCache = [];

    /**
     * Per-request cache of sibling-group satisfaction, keyed by patient id,
     * group key, then vaccine id, so the group scan runs once per
     * (patient, group, vaccine) not once per (patient, vaccine).
     *
     * @var array<int, array<string, array<int, bool>>>
     */
    private array $siblingGroupCache = [];

    /**
     * Per-request status matrix: [patient_id][vaccine_id] => ['status', 'earliest'].
     * The index page builds three queues (due / overdue / no_show) over the
     * same patient-vaccine matrix; computing each pair once and reusing the
     * result across the modes avoids a 3x full matrix scan.
     *
     * @var array<int, array<int, array{status: string, earliest: ?Carbon}>>
     */
    private array $statusMatrix = [];

    private ?Carbon $today = null;

    private function today(): Carbon
    {
        return $this->today ??= Carbon::today();
    }

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
        return $this->matrixFor($patient, $vaccine)['status'];
    }

    /**
     * Status + earliest dose date for a (patient, vaccine) pair, computed
     * once per request and reused by statusFor(), nextDoseDate() and queue().
     *
     * @return array{status: string, earliest: ?Carbon}
     */
    private function matrixFor(Patient $patient, Vaccine $vaccine): array
    {
        return $this->statusMatrix[$patient->id][$vaccine->id] ??= $this->computeMatrix($patient, $vaccine);
    }

    /**
     * @return array{status: string, earliest: ?Carbon}
     */
    private function computeMatrix(Patient $patient, Vaccine $vaccine): array
    {
        $schedules = $vaccine->schedules;
        $given = $this->givenByVaccine($patient, $vaccine->id);

        if ($this->siblingGroupSatisfied($patient, $vaccine)) {
            return ['status' => self::STATUS_COMPLETED, 'earliest' => null];
        }

        if ($this->unresolvedMissed($patient, $vaccine) !== null) {
            return ['status' => self::STATUS_NO_SHOW, 'earliest' => $this->nextDoseDate($patient, $vaccine)];
        }

        if ($schedules->isEmpty()) {
            return ['status' => self::STATUS_WAITING, 'earliest' => null];
        }

        if ($vaccine->repeat_months === null && $given->count() >= $schedules->count()) {
            return ['status' => self::STATUS_COMPLETED, 'earliest' => null];
        }

        $earliest = $this->nextDoseDate($patient, $vaccine);

        if ($earliest === null || $this->today()->lt($earliest)) {
            return ['status' => self::STATUS_WAITING, 'earliest' => $earliest];
        }

        if ($this->isOutOfWindow($patient, $vaccine, $earliest)) {
            return ['status' => self::STATUS_OUT_OF_WINDOW, 'earliest' => $earliest];
        }

        return ['status' => self::STATUS_OVERDUE, 'earliest' => $earliest];
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

        if ($this->siblingGroupSatisfied($patient, $vaccine)) {
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
        return $this->givenByVaccine($patient, $vaccine->id)->count() + 1;
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

        $given = $this->givenByVaccine($patient, $vaccine->id);
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

    public function projectedCompletionDate(Patient $patient, Vaccine $vaccine, ?Carbon $next = null): ?Carbon
    {
        $schedules = $vaccine->schedules;

        if ($schedules->isEmpty()) {
            return null;
        }

        $givenCount = $this->givenByVaccine($patient, $vaccine->id)->count();
        $next = $next ?? $this->nextDoseDate($patient, $vaccine, $givenCount);

        if ($next === null) {
            return null;
        }

        $cursor = $this->today()->greaterThan($next) ? $this->today() : $next->copy();

        for ($i = $givenCount; $i < $schedules->count() - 1; $i++) {
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
        $given = $records->where('no_show', false)->values();

        $this->assertScheduleConfigured($schedules);
        $this->assertSeriesNotComplete($vaccine, $given, $schedules);

        $nextIndex = $given->count();
        $doseNumber = $nextIndex + 1;
        $schedule = $schedules->get($nextIndex) ?? $schedules->last();
        $dateGiven = isset($data['date_given']) ? Carbon::parse($data['date_given']) : Carbon::today();

        $this->assertDoseNotTooEarly($patient, $vaccine, $nextIndex, $dateGiven);
        $this->assertOutOfWindowOverride($patient, $vaccine, $data);
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
            'child_weight_kg' => $data['child_weight_kg'] ?? null,
            'child_height_cm' => $data['child_height_cm'] ?? null,
            'administered_by' => $data['administered_by'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $this->logEvent($patient, $vaccine, ImmunizationStatusEvent::TYPE_ATTENDED, $doseNumber, [
            'event_date' => $dateGiven->toDateString(),
        ]);

        $this->forgetRecords($patient);
        $this->bumpQueueGeneration();

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

        $event = $this->logEvent($patient, $vaccine, ImmunizationStatusEvent::TYPE_MISSED, $doseNumber ?? ($records->where('no_show', false)->count() + 1), $data);

        $this->bumpQueueGeneration();

        return $event;
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

        $event = $this->logEvent($patient, $vaccine, ImmunizationStatusEvent::TYPE_CLEARED, $missed->dose_number, $data);

        $this->bumpQueueGeneration();

        return $event;
    }

    /**
     * Latest MISSED event that has not been superseded by an ATTENDED or
     * CLEARED event (or a real dose record) dated on/after the miss.
     */
    public function unresolvedMissed(Patient $patient, Vaccine $vaccine): ?ImmunizationStatusEvent
    {
        $events = $this->eventsByVaccine($patient, $vaccine->id);

        $missed = $events->first(fn (ImmunizationStatusEvent $event) => $event->event_type === ImmunizationStatusEvent::TYPE_MISSED);

        if ($missed === null) {
            return null;
        }

        $hasResolution = $events->contains(fn (ImmunizationStatusEvent $event) => in_array($event->event_type, [ImmunizationStatusEvent::TYPE_ATTENDED, ImmunizationStatusEvent::TYPE_CLEARED], true) && $event->event_date->gte($missed->event_date));

        if ($hasResolution) {
            return null;
        }

        $hasDoseAfterMiss = $this->givenByVaccine($patient, $vaccine->id)
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

        $motherId = $data['mother_id'] ?? null;
        $mother = $motherId !== null ? Patient::find($motherId) : null;

        $patient = Patient::create([
            'household_id' => $householdId,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'suffix' => $data['suffix'] ?? null,
            'sex' => $data['sex'],
            'date_of_birth' => $data['date_of_birth'],
            'birth_weight' => $data['birth_weight'] ?? null,
            'guardian_name' => $data['guardian_name'] ?? null,
            'mother_id' => $mother?->id,
            'mother_name' => $mother !== null
                ? trim(implode(' ', array_filter([$mother->first_name, $mother->middle_name, $mother->last_name])))
                : ($data['mother_name'] ?? ''),
            'father_name' => $data['father_name'] ?? null,
            'spouse_name' => '',
            'family_relationship' => $relationship,
            'residential_address' => $this->zoneAddress($householdId),
            'civil_status' => 'Single',
            'is_immunization_enrolled' => true,
        ]);

        $this->bumpQueueGeneration();

        return $patient;
    }

    /**
     * Enroll an existing patient in the immunization program.
     */
    public function enrollPatient(Patient $patient): Patient
    {
        $patient->is_immunization_enrolled = true;
        $patient->save();

        $this->bumpQueueGeneration();

        return $patient;
    }

    /**
     * Index queues: due / overdue / no_show.
     *
     * The overdue queue includes out-of-window patients (merged for
     * workflow simplicity). The clinical distinction is preserved at
     * administration time via the override-reason gate.
     *
     * The "due" queue uses the supplied date window (defaults to today
     * plus DUE_WINDOW_DAYS when omitted) so patients who became eligible
     * between session days are not missed; everything earlier than the
     * window start is "overdue".
     *
     * @param  list<string>  $categories
     * @return Collection<int, array{patient: Patient, vaccine: Vaccine, status: string, dose_number: int, due_date: Carbon}>
     */
    public function queue(string $mode, ?int $zoneId = null, ?Carbon $from = null, ?Carbon $to = null, array $categories = ['Child', 'Both']): Collection
    {
        return Cache::remember(
            $this->queueCacheKey($mode, $zoneId, $from, $to, $categories),
            self::QUEUE_CACHE_TTL,
            fn () => $this->buildQueue($mode, $zoneId, $from, $to, $categories)
        );
    }

    private function queueCacheKey(string $mode, ?int $zoneId, ?Carbon $from, ?Carbon $to, array $categories): string
    {
        $generation = (int) Cache::get(self::QUEUE_GENERATION_KEY, 0);

        return implode('_', [
            'immunization_queue',
            $generation,
            $mode,
            $zoneId ?? 'all',
            $from?->toDateString() ?? 'none',
            $to?->toDateString() ?? 'none',
            implode('-', $categories),
        ]);
    }

    /**
     * Invalidate cached queue results after any immunization write.
     */
    private function bumpQueueGeneration(): void
    {
        Cache::increment(self::QUEUE_GENERATION_KEY);
    }

    /**
     * @param  list<string>  $categories
     * @return Collection<int, array{patient: Patient, vaccine: Vaccine, status: string, dose_number: int, due_date: Carbon}>
     */
    private function buildQueue(string $mode, ?int $zoneId = null, ?Carbon $from = null, ?Carbon $to = null, array $categories = ['Child', 'Both']): Collection
    {
        $query = Patient::query()->whereHas('household')->where('is_immunization_enrolled', true);

        if ($zoneId !== null) {
            $query->whereHas('household', fn ($q) => $q->where('zone_id', $zoneId));
        }

        $patients = $query->with(['immunizationRecords', 'immunizationStatusEvents', 'household.zone'])->get();

        $vaccines = Vaccine::whereIn('category', $categories)->with('schedules')->get();

        $from = $from?->copy()->startOfDay() ?? Carbon::today();
        $to = $to?->copy()->endOfDay() ?? $from->copy()->addDays(self::DUE_WINDOW_DAYS);

        $childFocused = in_array('Child', $categories, true);

        $entries = [];

        foreach ($patients as $patient) {
            $age = $patient->age;

            if ($age === null || ($childFocused && $age >= 18) || (! $childFocused && $age < 18)) {
                continue;
            }

            foreach ($vaccines as $vaccine) {
                $matrix = $this->matrixFor($patient, $vaccine);
                $earliest = $matrix['earliest'];

                if ($earliest === null) {
                    continue;
                }

                $status = $matrix['status'];

                if ($mode === self::QUEUE_DUE) {
                    if (in_array($status, [self::STATUS_NO_SHOW, self::STATUS_OUT_OF_WINDOW, self::STATUS_COMPLETED], true)) {
                        continue;
                    }

                    if ($earliest->lt($from) || $earliest->gt($to)) {
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

                $matchesMode = $mode === self::STATUS_OVERDUE
                    ? in_array($status, [self::STATUS_OVERDUE, self::STATUS_OUT_OF_WINDOW], true)
                    : $status === $mode;

                if (! $matchesMode || ($mode === self::STATUS_OVERDUE && ! $earliest->lt($from))) {
                    continue;
                }

                if ($mode === self::STATUS_OVERDUE) {
                    $dosesGiven = $this->givenByVaccine($patient, $vaccine->id)->count();

                    if ($dosesGiven === 0 && $earliest->lt($from->copy()->subDays(self::OVERDUE_LOOKBACK_DAYS))) {
                        continue;
                    }
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

        return collect($entries);
    }

    /**
     * Attach a schedule-derived next due date to each recent record row.
     */
    public function withNextDue(Collection $records): Collection
    {
        $patientIds = $records->pluck('patient_id')->unique()->all();
        $vaccineIds = $records->pluck('vaccine_id')->unique()->all();

        if ($patientIds === [] || $vaccineIds === []) {
            return $records;
        }

        $patients = Patient::with('immunizationRecords')->whereIn('id', $patientIds)->get()->keyBy('id');
        $vaccines = Vaccine::with('schedules')->whereIn('id', $vaccineIds)->get()->keyBy('id');

        foreach ($records as $record) {
            $patient = $patients->get($record->patient_id);
            $vaccine = $vaccines->get($record->vaccine_id);

            $record->next_due = $patient !== null && $vaccine !== null
                ? $this->nextDoseDate($patient, $vaccine)?->toDateString()
                : null;
        }

        return $records;
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
            ->whereReal()
            ->whereIn('vaccine_id', $conflictingIds)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'vaccine_id' => 'This patient has already received another vaccine in the same group ('.$vaccine->group_key.').',
            ]);
        }
    }

    /**
     * @param  Collection<int, VaccineSchedule>  $schedules
     */
    private function assertScheduleConfigured(Collection $schedules): void
    {
        if ($schedules->isEmpty()) {
            throw ValidationException::withMessages([
                'vaccine_id' => 'This vaccine has no schedule configured.',
            ]);
        }
    }

    /**
     * @param  Collection<int, Immunization>  $given
     * @param  Collection<int, VaccineSchedule>  $schedules
     */
    private function assertSeriesNotComplete(Vaccine $vaccine, Collection $given, Collection $schedules): void
    {
        if ($vaccine->repeat_months === null && $given->count() >= $schedules->count()) {
            throw ValidationException::withMessages([
                'dose_number' => 'The series for this vaccine is already complete.',
            ]);
        }
    }

    private function assertDoseNotTooEarly(Patient $patient, Vaccine $vaccine, int $doseIndex, Carbon $dateGiven): void
    {
        $earliest = $this->nextDoseDate($patient, $vaccine, $doseIndex);

        if ($earliest !== null && $dateGiven->lt($earliest)) {
            throw ValidationException::withMessages([
                'date_given' => 'Too early for this dose. Earliest date: '.$earliest->format('M j, Y').'.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertOutOfWindowOverride(Patient $patient, Vaccine $vaccine, array $data): void
    {
        if ($this->isOutOfWindow($patient, $vaccine) && blank($data['override_reason'] ?? null)) {
            throw ValidationException::withMessages([
                'override_reason' => 'This vaccine is out of its age window; an override reason is required.',
            ]);
        }
    }

    /**
     * Whether another vaccine in the same group has already been administered.
     *
     * When one alternative (e.g. Hepa B birth dose versus late dose versus
     * catch-up) has been given, the group is considered fulfilled, so the other
     * group members must not surface as overdue/waiting anymore.
     */
    private function siblingGroupSatisfied(Patient $patient, Vaccine $vaccine): bool
    {
        if ($vaccine->group_key === null) {
            return false;
        }

        $existing = $this->siblingGroupCache[$patient->id][$vaccine->group_key][$vaccine->id] ?? null;

        if ($existing !== null) {
            return $existing;
        }

        $result = $this->computeSiblingGroupSatisfied($patient, $vaccine);
        $this->siblingGroupCache[$patient->id][$vaccine->group_key][$vaccine->id] = $result;

        return $result;
    }

    private function computeSiblingGroupSatisfied(Patient $patient, Vaccine $vaccine): bool
    {
        $groupMembers = $this->groupMemberCache[$vaccine->group_key] ??= Vaccine::where('group_key', $vaccine->group_key)->pluck('id');

        $siblingIds = $groupMembers->filter(fn ($id) => $id !== $vaccine->id);

        if ($siblingIds->isEmpty()) {
            return false;
        }

        return $this->patientRecords($patient)
            ->whereIn('vaccine_id', $siblingIds)
            ->where('no_show', false)
            ->isNotEmpty();
    }

    private function isOutOfWindow(Patient $patient, Vaccine $vaccine, ?Carbon $earliest = null): bool
    {
        if ($vaccine->complete_before_days === null) {
            return false;
        }

        if (($vaccine->start_after_days ?? 0) <= 0) {
            return false;
        }

        $projected = $this->projectedCompletionDate($patient, $vaccine, $earliest);

        return $projected !== null
            && $projected->greaterThan($patient->date_of_birth->copy()->addDays($vaccine->complete_before_days));
    }

    /**
     * @return Collection<int, Immunization>
     */
    private function recordsFor(Patient $patient, Vaccine $vaccine): Collection
    {
        return $this->recordsByVaccine($patient, $vaccine->id);
    }

    /**
     * A patient's records for one vaccine, sorted by dose number, fetched
     * once per (patient, vaccine) per request.
     *
     * @return Collection<int, Immunization>
     */
    private function recordsByVaccine(Patient $patient, int $vaccineId): Collection
    {
        if (! isset($this->recordsByVaccineCache[$patient->id])) {
            $this->recordsByVaccineCache[$patient->id] = $this->patientRecords($patient)
                ->groupBy('vaccine_id')
                ->map(fn (Collection $group) => $group->sortBy('dose_number')->values());
        }

        return $this->recordsByVaccineCache[$patient->id][$vaccineId] ?? collect();
    }

    /**
     * A patient's real (non-no-show) records for one vaccine, fetched once
     * per (patient, vaccine) per request.
     *
     * @return Collection<int, Immunization>
     */
    private function givenByVaccine(Patient $patient, int $vaccineId): Collection
    {
        if (! isset($this->givenByVaccineCache[$patient->id])) {
            $this->givenByVaccineCache[$patient->id] = $this->patientRecords($patient)
                ->where('no_show', false)
                ->groupBy('vaccine_id')
                ->map(fn (Collection $group) => $group->values());
        }

        return $this->givenByVaccineCache[$patient->id][$vaccineId] ?? collect();
    }

    /**
     * A patient's status events for one vaccine, sorted by event date
     * descending, fetched once per (patient, vaccine) per request.
     *
     * @return Collection<int, ImmunizationStatusEvent>
     */
    private function eventsByVaccine(Patient $patient, int $vaccineId): Collection
    {
        if (! isset($this->eventsByVaccineCache[$patient->id])) {
            $this->eventsByVaccineCache[$patient->id] = $this->statusEventsFor($patient)
                ->groupBy('vaccine_id')
                ->map(fn (Collection $group) => $group->sortByDesc('event_date')->values());
        }

        return $this->eventsByVaccineCache[$patient->id][$vaccineId] ?? collect();
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
     * All of a patient's status events, sorted by event date, fetched once per request.
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
        unset($this->recordsByVaccineCache[$patient->id]);
        unset($this->givenByVaccineCache[$patient->id]);
        unset($this->siblingGroupCache[$patient->id]);
        unset($this->statusMatrix[$patient->id]);
        $patient->unsetRelation('immunizationRecords');
    }

    private function forgetEvents(Patient $patient): void
    {
        unset($this->eventsCache[$patient->id]);
        unset($this->eventsByVaccineCache[$patient->id]);
        unset($this->statusMatrix[$patient->id]);
        $patient->unsetRelation('immunizationStatusEvents');
    }

    private function zoneAddress(int $householdId): string
    {
        $zone = Household::with('zone')->find($householdId)?->zone;

        return $zone !== null ? 'Purok '.$zone->zone_number.', Barangay Sta. Ana' : '';
    }
}
