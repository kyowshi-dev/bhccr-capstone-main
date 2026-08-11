<?php

namespace App\Services;

use App\Models\FamilyPlanningClient;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Pregnancy;
use Illuminate\Database\Eloquent\Collection;

class MaternalQueryService
{
    /**
     * @param  array{zone_id?: int|null, search?: string|null}  $filters
     */
    public function activePregnancies(array $filters = []): Collection
    {
        $query = Pregnancy::query()
            ->with(['patient.household.zone', 'visits'])
            ->where('status', Pregnancy::STATUS_ACTIVE);

        $this->applyPatientFilters($query, $filters);

        return $query->orderBy('edc')->get();
    }

    public function pregnanciesForPatient(Patient $patient): Collection
    {
        return $patient->pregnancies()
            ->with('visits')
            ->orderByDesc('lmp')
            ->get();
    }

    /**
     * @param  array{zone_id?: int|null, search?: string|null}  $filters
     */
    public function familyPlanningClients(array $filters = []): Collection
    {
        $query = FamilyPlanningClient::query()
            ->with(['patient.household.zone'])
            ->latest();

        $this->applyPatientFilters($query, $filters);

        return $query->get();
    }

    /**
     * @param  array{zone_id?: int|null, search?: string|null}  $filters
     */
    public function postnatalRecords(array $filters = []): Collection
    {
        $query = PostnatalRecord::query()
            ->with(['patient.household.zone', 'pregnancy'])
            ->latest('delivery_date');

        $this->applyPatientFilters($query, $filters);

        return $query->get();
    }

    public function postnatalForPatient(Patient $patient): Collection
    {
        return $patient->postnatalRecords()
            ->with('pregnancy')
            ->orderByDesc('delivery_date')
            ->get();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $query
     * @param  array{zone_id?: int|null, search?: string|null}  $filters
     */
    private function applyPatientFilters($query, array $filters): void
    {
        $query->whereHas('patient', function ($q) use ($filters) {
            $q->whereHas('household', function ($hq) use ($filters) {
                if (! empty($filters['zone_id'])) {
                    $hq->where('zone_id', $filters['zone_id']);
                }

                if (! empty($filters['search'])) {
                    $search = '%'.addcslashes((string) $filters['search'], '%_').'%';
                    $hq->where('family_name_head', 'like', $search);
                }
            });

            if (! empty($filters['search'])) {
                $search = '%'.addcslashes((string) $filters['search'], '%_').'%';
                $q->where(function ($name) use ($search) {
                    $name->where('last_name', 'like', $search)
                        ->orWhere('first_name', 'like', $search)
                        ->orWhere('middle_name', 'like', $search);
                });
            }
        });
    }
}
