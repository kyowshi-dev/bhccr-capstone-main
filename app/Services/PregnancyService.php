<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Pregnancy;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class PregnancyService
{
    public const EDC_DAYS = 280;

    /**
     * Register a new active pregnancy for a patient.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(Patient $patient, array $data, ?int $workerId): Pregnancy
    {
        $this->assertNoActivePregnancy($patient);

        return Pregnancy::create($this->preparePayload($patient, [
            ...$data,
            'recorded_by' => $workerId,
            'status' => Pregnancy::STATUS_ACTIVE,
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Pregnancy $pregnancy, array $data): Pregnancy
    {
        $pregnancy->update($this->withComputedDates($data));

        return $pregnancy;
    }

    public function markDelivered(Pregnancy $pregnancy): void
    {
        if ($pregnancy->status === Pregnancy::STATUS_ACTIVE) {
            $pregnancy->update(['status' => Pregnancy::STATUS_DELIVERED]);
        }
    }

    public function close(Pregnancy $pregnancy): void
    {
        $pregnancy->update(['status' => Pregnancy::STATUS_CLOSED]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePayload(Patient $patient, array $data): array
    {
        return $this->withComputedDates([
            'patient_id' => $patient->id,
            ...$data,
        ]);
    }

    /**
     * Fill EDC (LMP + 280 days) and AOG (weeks since LMP) unless overridden.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withComputedDates(array $data): array
    {
        $lmp = Carbon::parse($data['lmp']);

        if (blank($data['edc'] ?? null)) {
            $data['edc'] = $lmp->copy()->addDays(self::EDC_DAYS)->toDateString();
        }

        if (blank($data['aog_weeks'] ?? null)) {
            $data['aog_weeks'] = (int) floor($lmp->diffInDays(Carbon::today()) / 7);
        }

        return $data;
    }

    private function assertNoActivePregnancy(Patient $patient): void
    {
        $hasActive = $patient->pregnancies()
            ->where('status', Pregnancy::STATUS_ACTIVE)
            ->exists();

        if ($hasActive) {
            throw ValidationException::withMessages([
                'lmp' => 'This patient already has an active pregnancy. Close or mark it delivered first.',
            ]);
        }
    }
}
