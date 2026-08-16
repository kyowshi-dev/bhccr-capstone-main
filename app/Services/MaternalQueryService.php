<?php

namespace App\Services;

use App\Models\FamilyPlanningClient;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Pregnancy;
use App\Models\PrenatalVisit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

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

        return [
            'activePregnancies' => Pregnancy::where('status', Pregnancy::STATUS_ACTIVE)->count(),
            'postnatalMothers' => $this->activePostnatalRecordsQuery()->count(),
            'fpClients' => FamilyPlanningClient::where('is_active', true)->count(),
            'followUpsDue' => $this->followUpsDuePatientIds($today)->count(),
        ];
    }

    /**
     * @param  array{zone_id?: int|null, search?: string|null}  $filters
     */
    public function activePregnancies(array $filters = []): Collection
    {
        $query = Pregnancy::query()
            ->with(['patient.household.zone'])
            ->withCount('visits')
            ->selectSub(
                PrenatalVisit::query()
                    ->select('next_visit_date')
                    ->whereColumn('prenatal_visits.pregnancy_id', 'pregnancies.id')
                    ->orderByDesc('visit_date')
                    ->orderByDesc('id')
                    ->limit(1),
                'latest_next_visit_date',
            )
            ->where('status', Pregnancy::STATUS_ACTIVE);

        $this->applyPatientFilters($query, $filters);

        return $query->orderBy('edc')->get();
    }

    public function pregnanciesForPatient(Patient $patient): Collection
    {
        return $patient->pregnancies()
            ->with(['visits.consultation.vitals'])
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
     * Unique patient ids whose scheduled follow-up is due within the next 7 days,
     * across all three maternal registers.
     *
     * @return SupportCollection<int, int>
     */
    private function followUpsDuePatientIds(Carbon $today): SupportCollection
    {
        return $this->prenatalDuePatientIds($today)
            ->merge($this->fpDuePatientIds($today))
            ->merge($this->postnatalDuePatientIds($today))
            ->unique()
            ->values();
    }

    /**
     * Patients with an active pregnancy that has at least one visit whose
     * next visit date falls on or before the 7-day cutoff.
     *
     * @return SupportCollection<int, int>
     */
    private function prenatalDuePatientIds(Carbon $today): SupportCollection
    {
        $cutoff = $today->copy()->addDays(7);

        return Pregnancy::query()
            ->where('status', Pregnancy::STATUS_ACTIVE)
            ->whereHas('visits', fn (Builder $query) => $query
                ->whereNotNull('next_visit_date')
                ->where('next_visit_date', '<=', $cutoff))
            ->pluck('patient_id');
    }

    /**
     * @return SupportCollection<int, int>
     */
    private function fpDuePatientIds(Carbon $today): SupportCollection
    {
        $cutoff = $today->copy()->addDays(7);

        return FamilyPlanningClient::query()
            ->where('is_active', true)
            ->whereNotNull('schedule_next_visit')
            ->where('schedule_next_visit', '<=', $cutoff)
            ->pluck('patient_id');
    }

    /**
     * Mothers still inside the 24h/7d/14d/28d postpartum window (at least one
     * slot still open on or before its closing date).
     *
     * @return SupportCollection<int, int>
     */
    private function postnatalDuePatientIds(Carbon $today): SupportCollection
    {
        return PostnatalRecord::query()
            ->where(function (Builder $query) use ($today) {
                foreach (PostnatalRecord::POSTPARTUM_SLOTS as $column => $days) {
                    $query->orWhere(function (Builder $slot) use ($column, $days, $today) {
                        $slot->whereNull($column)
                            ->where('delivery_date', '<=', $today->copy()->addDays(7 - $days));
                    });
                }
            })
            ->pluck('patient_id');
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

        return $query->where(function (Builder $q) {
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
        $query->whereHas('patient', function (Builder $q) use ($filters) {
            $q->whereHas('household', function (Builder $hq) use ($filters) {
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
                $q->where(function (Builder $name) use ($search) {
                    $name->where('last_name', 'like', $search)
                        ->orWhere('first_name', 'like', $search)
                        ->orWhere('middle_name', 'like', $search);
                });
            }
        });
    }
}
