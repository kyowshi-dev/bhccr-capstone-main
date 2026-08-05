<?php

namespace App\Services;

use App\Models\Immunization;
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

        if ($records->contains(fn (Immunization $record) => $record->no_show)) {
            return self::STATUS_NO_SHOW;
        }

        $schedules = $vaccine->schedules;

        if ($schedules->isEmpty()) {
            return self::STATUS_WAITING;
        }

        $given = $records->where('no_show', false)->count();

        if ($given >= $schedules->count()) {
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

        if ($schedules->isEmpty() || $records->where('no_show', false)->count() >= $schedules->count()) {
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

        return $vaccine->schedules->get($nextIndex);
    }

    public function nextDoseNumber(Patient $patient, Vaccine $vaccine): int
    {
        return $this->recordsFor($patient, $vaccine)->where('no_show', false)->count() + 1;
    }

    public function nextDueDate(Immunization $record): ?Carbon
    {
        $schedule = VaccineSchedule::where('vaccine_id', $record->vaccine_id)
            ->where('dose_number', $record->dose_number)
            ->first();

        if ($schedule === null || $schedule->gap_days === null) {
            return null;
        }

        return $record->date_given->copy()->addDays($schedule->gap_days);
    }

    /**
     * Earliest date the next dose may be given (birth-age rule vs gap since last dose).
     */
    public function nextDoseDate(Patient $patient, Vaccine $vaccine, ?int $doseIndex = null): ?Carbon
    {
        $schedules = $vaccine->schedules;

        if ($schedules->isEmpty()) {
            return null;
        }

        $nextIndex = $doseIndex ?? $this->recordsFor($patient, $vaccine)->where('no_show', false)->count();
        $schedule = $schedules->get($nextIndex);

        if ($schedule === null) {
            return null;
        }

        return $patient->date_of_birth->copy()->addDays($schedule->min_age_days);
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

        if ($given->count() >= $schedules->count()) {
            throw ValidationException::withMessages([
                'dose_number' => 'The series for this vaccine is already complete.',
            ]);
        }

        $nextIndex = $given->count();
        $doseNumber = $nextIndex + 1;
        $schedule = $schedules->get($nextIndex);
        $earliest = $this->nextDoseDate($patient, $vaccine, $nextIndex);

        if ($earliest !== null && Carbon::today()->lt($earliest)) {
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

        $dateGiven = isset($data['date_given']) ? Carbon::parse($data['date_given']) : Carbon::today();

        $patient->immunizationRecords()
            ->where('vaccine_id', $vaccine->id)
            ->where('dose_number', $doseNumber)
            ->where('no_show', true)
            ->delete();

        return Immunization::create([
            'patient_id' => $patient->id,
            'vaccine_id' => $vaccine->id,
            'dose_number' => $doseNumber,
            'date_given' => $dateGiven,
            'temp_recorded' => $data['temp_recorded'] ?? null,
            'administered_by' => $data['administered_by'] ?? null,
            'next_due_date' => $schedule->gap_days !== null ? $dateGiven->copy()->addDays($schedule->gap_days) : null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function markNoShow(Patient $patient, Vaccine $vaccine): Immunization
    {
        $records = $this->recordsFor($patient, $vaccine);
        $schedules = $vaccine->schedules;

        if ($schedules->isEmpty() || $records->where('no_show', false)->count() >= $schedules->count()) {
            throw ValidationException::withMessages([
                'dose_number' => 'The series for this vaccine is already complete.',
            ]);
        }

        $doseNumber = $records->where('no_show', false)->count() + 1;

        $patient->immunizationRecords()
            ->where('vaccine_id', $vaccine->id)
            ->where('dose_number', $doseNumber)
            ->where('no_show', true)
            ->delete();

        return Immunization::create([
            'patient_id' => $patient->id,
            'vaccine_id' => $vaccine->id,
            'dose_number' => $doseNumber,
            'date_given' => Carbon::today(),
            'no_show' => true,
            'no_show_at' => Carbon::now(),
        ]);
    }

    public function clearNoShow(Immunization $record): void
    {
        $record->delete();
    }

    /**
     * Index queues: due / overdue / out_of_window / no_show.
     *
     * @return Collection<int, array{patient: Patient, vaccine: Vaccine, status: string, dose_number: int, due_date: Carbon|null}>
     */
    public function queue(string $mode, ?int $zoneId = null, ?string $date = null): Collection
    {
        $query = Patient::query()->whereHas('household');

        if ($zoneId !== null) {
            $query->whereHas('household', fn ($q) => $q->where('zone_id', $zoneId));
        }

        $patients = $query->with(['immunizationRecords', 'household.zone'])->get();

        $vaccines = Vaccine::whereIn('category', ['Child', 'Both'])->with('schedules')->get();

        $target = $date !== null ? Carbon::parse($date) : Carbon::today();

        $entries = [];

        foreach ($patients as $patient) {
            foreach ($vaccines as $vaccine) {
                if ($mode === self::QUEUE_DUE) {
                    $earliest = $this->nextDoseDate($patient, $vaccine);

                    if ($earliest === null || ! $earliest->isSameDay($target)) {
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

                $status = $this->statusFor($patient, $vaccine);

                if ($status !== $mode) {
                    continue;
                }

                $entries[] = [
                    'patient' => $patient,
                    'vaccine' => $vaccine,
                    'status' => $status,
                    'dose_number' => $this->nextDoseNumber($patient, $vaccine),
                    'due_date' => $this->nextDoseDate($patient, $vaccine),
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
        return $patient->immunizationRecords()
            ->where('vaccine_id', $vaccine->id)
            ->orderBy('dose_number')
            ->get();
    }
}
