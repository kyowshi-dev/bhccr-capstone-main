<?php

namespace App\Services;

use App\Models\FamilyPlanningClient;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Pregnancy;
use Carbon\Carbon;
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

    // ------------------------------------------------------------------
    // Midwife dashboard action queue & watch list
    // ------------------------------------------------------------------

    /**
     * @return array<string, Collection>
     */
    public function actionQueue(Carbon $today): array
    {
        $todayStr = $today->toDateString();
        $nextWeek = $today->copy()->addDays(7)->toDateString();
        $nextMonth = $today->copy()->addDays(30)->toDateString();

        $pregnancies = Pregnancy::query()
            ->where('status', Pregnancy::STATUS_ACTIVE)
            ->with(['patient.household.zone', 'visits'])
            ->orderBy('edc')
            ->get();

        $overdue = $pregnancies->filter(function (Pregnancy $p) use ($todayStr) {
            $nextVisit = $p->visits->sortByDesc('visit_date')->first()?->next_visit_date;

            return $nextVisit !== null
                && $nextVisit->toDateString() < $todayStr;
        })->values();

        $dueSoon = $pregnancies->filter(function (Pregnancy $p) use ($todayStr, $nextWeek) {
            $nextVisit = $p->visits->sortByDesc('visit_date')->first()?->next_visit_date;

            return $nextVisit !== null
                && $nextVisit->toDateString() >= $todayStr
                && $nextVisit->toDateString() <= $nextWeek;
        })->values();

        $dueThisMonth = $pregnancies->filter(function (Pregnancy $p) use ($todayStr, $nextMonth) {
            return $p->edc !== null
                && $p->edc->toDateString() >= $todayStr
                && $p->edc->toDateString() <= $nextMonth;
        })->values();

        $postnatalSlots = $this->openPostnatalSlots($today);

        return [
            'overdue' => $overdue,
            'due_soon' => $dueSoon,
            'due_this_month' => $dueThisMonth,
            'postnatal_slots' => $postnatalSlots,
        ];
    }

    public function watchlist(): Collection
    {
        return Pregnancy::query()
            ->where('status', Pregnancy::STATUS_ACTIVE)
            ->whereNotNull('risk_flags')
            ->with(['patient.household.zone', 'visits'])
            ->orderBy('edc')
            ->get()
            ->filter(fn (Pregnancy $p) => ! empty($p->risk_flags));
    }

    /**
     * @return Collection<int, PostnatalRecord>
     */
    private function openPostnatalSlots(Carbon $today): Collection
    {
        $slots = ['postpartum_24h_date' => 1, 'postpartum_7d_date' => 7, 'postpartum_14d_date' => 14, 'postpartum_28d_date' => 28];

        return PostnatalRecord::query()
            ->with(['patient.household.zone'])
            ->whereDate('delivery_date', '<=', $today->toDateString())
            ->get()
            ->filter(function (PostnatalRecord $record) use ($slots, $today) {
                foreach ($slots as $column => $days) {
                    if ($record->{$column} !== null) {
                        continue;
                    }

                    if (Carbon::parse($record->delivery_date)->addDays($days)->lte($today)) {
                        return true;
                    }
                }

                return false;
            });
    }

    // ------------------------------------------------------------------
    // Midwife dashboard KPIs
    // ------------------------------------------------------------------

    public function prenatalRegistrants(): int
    {
        return Pregnancy::where('status', Pregnancy::STATUS_ACTIVE)->count();
    }

    public function dueThisMonth(Carbon $today): int
    {
        return Pregnancy::where('status', Pregnancy::STATUS_ACTIVE)
            ->whereBetween('edc', [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()])
            ->count();
    }

    public function postnatalDue(Carbon $today): int
    {
        $slots = [
            'postpartum_24h_date' => 1,
            'postpartum_7d_date' => 7,
            'postpartum_14d_date' => 14,
            'postpartum_28d_date' => 28,
        ];

        $records = PostnatalRecord::query()
            ->whereDate('delivery_date', '<=', $today->toDateString())
            ->get();

        $due = 0;

        foreach ($records as $record) {
            foreach ($slots as $column => $windowDays) {
                if ($record->{$column} !== null) {
                    continue;
                }

                if (Carbon::parse($record->delivery_date)->addDays($windowDays)->lte($today)) {
                    $due++;

                    break;
                }
            }
        }

        return $due;
    }

    public function highRiskReferrals(): int
    {
        return PostnatalRecord::query()
            ->whereNotNull('danger_signs_mother')
            ->get()
            ->filter(fn (PostnatalRecord $record) => ! empty($record->danger_signs_mother))
            ->count();
    }

    // ------------------------------------------------------------------

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
