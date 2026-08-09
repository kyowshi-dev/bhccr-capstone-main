<?php

namespace App\Services;

use App\Models\Pregnancy;
use App\Models\PrenatalVisit;

class PrenatalVisitService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function add(Pregnancy $pregnancy, array $data, ?int $workerId): PrenatalVisit
    {
        return PrenatalVisit::create([
            'pregnancy_id' => $pregnancy->id,
            'consultation_id' => $data['consultation_id'] ?? null,
            'visit_date' => $data['visit_date'],
            'fundic_height_cm' => $data['fundic_height_cm'] ?? null,
            'fetal_heart_tone_bpm' => $data['fetal_heart_tone_bpm'] ?? null,
            'next_visit_date' => $data['next_visit_date'] ?? null,
            'recorded_by' => $workerId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PrenatalVisit $visit, array $data): PrenatalVisit
    {
        $visit->update([
            'consultation_id' => $data['consultation_id'] ?? null,
            'visit_date' => $data['visit_date'],
            'fundic_height_cm' => $data['fundic_height_cm'] ?? null,
            'fetal_heart_tone_bpm' => $data['fetal_heart_tone_bpm'] ?? null,
            'next_visit_date' => $data['next_visit_date'] ?? null,
        ]);

        return $visit;
    }

    public function countFor(Pregnancy $pregnancy): int
    {
        return $pregnancy->visits()->count();
    }
}
