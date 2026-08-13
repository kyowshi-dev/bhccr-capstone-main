<?php

namespace App\Services;

use App\Models\FamilyPlanningClient;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Pregnancy;
use App\Models\PrenatalVisit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class MaternalQueryService
{
    /**
     * Counts for the midwife "Maternal & Family Planning Register" overview.
     * Each count mirrors the default (active) view of its register table so
     * KPI card numbers always equal the default table rows.
     *
     * @return array{activePregnancies: int, postnatalMothers: int, fpClients: int, followUpsDue: int}
     */
    public function overviewCounts(): array
    {
        $today = Carbon::today();
        $cutoff = $today->copy()->addDays(7);

        $prenatalDuePatientIds = PrenatalVisit::query()
            ->whereNotNull('next_visit_date')
            ->whereDate('next_visit_date', '<=', $cutoff)
            ->whereHas('pregnancy', fn ($q) => $q->where('status', Pregnancy::STATUS_ACTIVE))
            ->with('pregnancy')
            ->get()
            ->groupBy('pregnancy_id')
            ->map(fn ($visits) => $visits->sortByDesc('visit_date')->first())
            ->pluck('pregnancy.patient_id');

        $fpDuePatientIds = FamilyPlanningClient::query()
            ->where('is_active', true)
            ->whereNotNull('schedule_next_visit')
            ->whereDate('schedule_next_visit', '<=', $cutoff)
            ->pluck('patient_id');

        $postnatalDuePatientIds = PostnatalRecord::query()
            ->where(function ($query) use ($today) {
                foreach (PostnatalRecord::POSTPARTUM_SLOTS as $column => $days) {
                    $query->orWhere(function ($slot) use ($column, $days, $today) {
                        $slot->whereNull($column)
                            ->whereDate('delivery_date', '<=', $today->copy()->addDays(7 - $days));
                    });
                }
            })
            ->pluck('patient_id');

        return [
            'activePregnancies' => Pregnancy::where('status', Pregnancy::STATUS_ACTIVE)->count(),
            'postnatalMothers' => $this->activePostnatalRecordsQuery()->count(),
            'fpClients' => FamilyPlanningClient::where('is_active', true)->count(),
            'followUpsDue' => $prenatalDuePatientIds
                ->merge($fpDuePatientIds)
                ->merge($postnatalDuePatientIds)
                ->unique()
                ->count(),
        ];
    }

    /**
     * @param  array{zone_id?: int|null, search?: string|null, status?: string|null}  $filters
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
     * @param  array{zone_id?: int|null, search?: string|null, status?: string|null}  $filters
     */
    public function familyPlanningClients(array $filters = []): Collection
    {
        $query = FamilyPlanningClient::query()
            ->with(['patient.household.zone'])
            ->latest();

        if (($filters['status'] ?? 'active') === 'active') {
            $query->where('is_active', true);
        }

        $this->applyPatientFilters($query, $filters);

        return $query->get();
    }

    /**
     * @param  array{zone_id?: int|null, search?: string|null, status?: string|null}  $filters
     */
    public function postnatalRecords(array $filters = []): Collection
    {
        $query = PostnatalRecord::query()
            ->with(['patient.household.zone', 'pregnancy'])
            ->latest('delivery_date');

        if (($filters['status'] ?? 'active') === 'active') {
            $query = $this->activePostnatalRecordsQuery($query);
        }

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
     * Scope records to mothers still inside the 24h/7d/14d/28d window
     * (at least one postpartum slot still open).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<*>|null  $query
     * @return \Illuminate\Database\Eloquent\Builder<*>
     */
    private function activePostnatalRecordsQuery($query = null)
    {
        $query ??= PostnatalRecord::query();

        return $query->where(function ($q) {
            foreach (array_keys(PostnatalRecord::POSTPARTUM_SLOTS) as $column) {
                $q->orWhereNull($column);
            }
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $query
     * @param  array{zone_id?: int|null, search?: string|null, status?: string|null}  $filters
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
