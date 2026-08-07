<?php

namespace App\Services;

use App\Models\FamilyPlanningClient;
use App\Models\FamilyPlanningVisit;
use App\Models\Patient;

class FamilyPlanningService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function register(Patient $patient, array $data, ?int $workerId): FamilyPlanningClient
    {
        return FamilyPlanningClient::create([
            'patient_id' => $patient->id,
            'type_of_client' => $data['type_of_client'],
            'method' => $data['method'],
            'drop_out_reason' => $data['drop_out_reason'] ?? null,
            'schedule_next_visit' => $data['schedule_next_visit'] ?? null,
            'is_active' => $data['type_of_client'] !== FamilyPlanningClient::TYPE_DROP_OUT,
            'recorded_by' => $workerId,
        ]);
    }

    /**
     * Log a follow-up visit and roll the client's method/schedule forward.
     *
     * @param  array<string, mixed>  $data
     */
    public function addVisit(FamilyPlanningClient $client, array $data, ?int $workerId): FamilyPlanningVisit
    {
        $visit = FamilyPlanningVisit::create([
            'client_id' => $client->id,
            'visit_date' => $data['visit_date'],
            'method' => $data['method'],
            'schedule_next_visit' => $data['schedule_next_visit'] ?? null,
            'recorded_by' => $workerId,
        ]);

        $client->update([
            'method' => $data['method'],
            'schedule_next_visit' => $data['schedule_next_visit'] ?? null,
            'is_active' => true,
            'type_of_client' => $client->type_of_client === FamilyPlanningClient::TYPE_DROP_OUT
                ? FamilyPlanningClient::TYPE_CONTINUING_USER
                : $client->type_of_client,
        ]);

        return $visit;
    }
}
