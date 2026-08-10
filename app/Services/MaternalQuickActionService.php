<?php

namespace App\Services;

use App\Models\FamilyPlanningClient;
use App\Models\FamilyPlanningVisit;
use App\Models\HealthWorker;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Pregnancy;
use App\Models\PrenatalVisit;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

final class MaternalQuickActionService
{
    public function __construct(
        private readonly PregnancyService $pregnancyService,
        private readonly MaternalIntakeService $intakeService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string}
     */
    public function execute(string $action, Patient $patient, array $data, HealthWorker $worker): array
    {
        return match ($action) {
            'register' => $this->registerLight($patient, $data, $worker->id),
            'log_prenatal_visit' => $this->logPrenatalVisit($patient, $data, $worker),
            'log_postpartum' => $this->logPostpartum($patient, $data, $worker),
            'log_fp_visit' => $this->logFamilyPlanningVisit($patient, $data, $worker),
            default => throw new \InvalidArgumentException("Unknown maternal quick action: {$action}"),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string}
     */
    private function registerLight(Patient $patient, array $data, int $workerId): array
    {
        $pregnancy = $this->pregnancyService->register($patient, [
            'lmp' => $data['lmp'],
            'risk_flags' => ! empty($data['risk_flags']) ? $data['risk_flags'] : null,
            'syphilis_result' => 'negative',
            'penicillin' => 'no',
        ], $workerId);

        return [
            'success' => true,
            'message' => 'Pregnancy registered. EDC '.($pregnancy->edc?->format('M d, Y') ?? '—').'.',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string}
     */
    private function logPrenatalVisit(Patient $patient, array $data, HealthWorker $worker): array
    {
        $pregnancy = $this->findActivePregnancy($patient);

        $consultationId = $this->intakeService->recordEncounter(
            $patient,
            'Prenatal',
            $data,
            $worker,
            $pregnancy,
        );

        PrenatalVisit::create([
            'pregnancy_id' => $pregnancy->id,
            'consultation_id' => $consultationId,
            'visit_date' => $data['visit_date'],
            'fundic_height_cm' => $data['fundic_height_cm'] ?? null,
            'fetal_heart_tone_bpm' => $data['fetal_heart_tone_bpm'] ?? null,
            'next_visit_date' => $data['next_visit_date'] ?? null,
            'recorded_by' => $worker->id,
        ]);

        return ['success' => true, 'message' => 'Prenatal visit logged.'];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string}
     */
    private function logPostpartum(Patient $patient, array $data, HealthWorker $worker): array
    {
        $record = PostnatalRecord::where('patient_id', $patient->id)
            ->orderByDesc('delivery_date')
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'action' => 'No postnatal record found for this patient.',
            ]);
        }

        $slotFilled = $this->fillOpenPostpartumSlot($record);

        $pregnancy = $record->pregnancy ?? null;

        $consultationId = $this->intakeService->recordEncounter(
            $patient,
            'Postpartum',
            $data,
            $worker,
            $pregnancy,
        );

        $record->update(['consultation_id' => $consultationId]);

        return ['success' => true, 'message' => $slotFilled.'.'];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{success: bool, message: string}
     */
    private function logFamilyPlanningVisit(Patient $patient, array $data, HealthWorker $worker): array
    {
        $client = FamilyPlanningClient::where('patient_id', $patient->id)
            ->where('is_active', true)
            ->first();

        if (! $client) {
            throw ValidationException::withMessages([
                'action' => 'No active family planning client found for this patient.',
            ]);
        }

        $pregnancy = $this->intakeService->activePregnancyFor($patient);

        $consultationId = $this->intakeService->recordEncounter(
            $patient,
            'Family Planning',
            $data,
            $worker,
            $pregnancy,
        );

        FamilyPlanningVisit::create([
            'client_id' => $client->id,
            'consultation_id' => $consultationId,
            'visit_date' => $data['visit_date'],
            'method' => $data['method'],
            'schedule_next_visit' => $data['next_visit_date'] ?? null,
            'recorded_by' => $worker->id,
        ]);

        return ['success' => true, 'message' => 'Family planning visit logged.'];
    }

    private function findActivePregnancy(Patient $patient): Pregnancy
    {
        /** @var Pregnancy */
        $pregnancy = $patient->pregnancies()
            ->where('status', Pregnancy::STATUS_ACTIVE)
            ->first()
            ?: throw ValidationException::withMessages([
                'action' => 'No active pregnancy found. Register a pregnancy first.',
            ]);

        return $pregnancy;
    }

    private function fillOpenPostpartumSlot(PostnatalRecord $record): string
    {
        $today = Carbon::today();
        $slots = [
            'postpartum_24h_date' => [1, '24-hour follow-up'],
            'postpartum_7d_date' => [7, '7-day follow-up'],
            'postpartum_14d_date' => [14, '14-day follow-up'],
            'postpartum_28d_date' => [28, '28-day follow-up'],
        ];

        foreach ($slots as $column => [$days, $label]) {
            if ($record->{$column} !== null) {
                continue;
            }

            if (Carbon::parse($record->delivery_date)->addDays($days)->lte($today)) {
                $record->update([$column => $today->toDateString()]);

                return $label.' logged';
            }
        }

        throw ValidationException::withMessages([
            'action' => 'No open postpartum follow-up window for this patient.',
        ]);
    }
}
