<?php

namespace App\Services;

use App\Enums\ConsultationStatus;
use App\Models\Patient;
use App\Models\User;
use App\Models\Vaccine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-side queries for the role-specific dashboards.
 */
final class DashboardQueryService
{
    public static function totalPatients(?User $user = null): int
    {
        $query = DB::table('patients');

        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('household_id', $user->accessibleHouseholdIds());
        }

        return $query->count();
    }

    public static function consultationsToday(Carbon $today, ?User $user = null): int
    {
        $query = DB::table('consultations')
            ->whereDate('created_at', $today);

        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('patient_id', $user->accessiblePatientIds());
        }

        return $query->count();
    }

    public static function activeConsultations(?User $user = null): int
    {
        $query = DB::table('consultations')
            ->whereIn('status', ConsultationStatus::activeValues());

        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('patient_id', $user->accessiblePatientIds());
        }

        return $query->count();
    }

    public static function followUpsToday(Carbon $today): int
    {
        return DB::table('consultations')
            ->whereDate('created_at', $today)
            ->where('nature_of_visit', 'Follow-up')
            ->count();
    }

    public static function overdueImmunizations(Carbon $today): int
    {
        $patients = Patient::query()
            ->whereHas('household')
            ->with('immunizationRecords')
            ->get();

        $vaccines = Vaccine::whereIn('category', ['Child', 'Adult', 'Both'])
            ->with('schedules')
            ->get();

        $service = app(ChildImmunizationService::class);
        $count = 0;

        foreach ($patients as $patient) {
            foreach ($vaccines as $vaccine) {
                $status = $service->statusFor($patient, $vaccine);

                if (in_array($status, ['overdue', 'out_of_window'], true)) {
                    $count++;
                    break;
                }
            }
        }

        return $count;
    }

    public static function recentActivity(int $limit = 5, ?User $user = null): Collection
    {
        $query = DB::table('audit_logs')
            ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
            ->select('audit_logs.*', 'users.username')
            ->orderByDesc('audit_logs.created_at')
            ->limit($limit);

        // Zone-scoped workers only see their own activity on the dashboard.
        if ($user !== null && $user->isZoneScoped()) {
            $query->where('audit_logs.user_id', $user->id);
        }

        return $query->get();
    }

    /**
     * Terminal consultations with their diagnosis summaries, used by the
     * "results ready" handout panel on each dashboard.
     *
     * @param  array{query?: string, from?: string, to?: string}  $inputs
     * @return array{resultsReady: Collection, resultsReadyCount: int, resultsFilters: array{query: string, from: string, to: string}}
     */
    public static function resultsReady(array $inputs, int $limit = 15, bool $defaultToToday = false, ?User $user = null): array
    {
        $resultsQuery = DB::table('consultations')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->whereIn('consultations.status', ConsultationStatus::terminalValues())
            ->select(
                'consultations.id',
                'consultations.updated_at',
                'patients.first_name',
                'patients.last_name',
                'patients.id as patient_id'
            )
            ->orderByDesc('consultations.updated_at');

        if ($user !== null && $user->isZoneScoped()) {
            $resultsQuery->whereIn('patients.household_id', $user->accessibleHouseholdIds());
        }

        if (! empty($inputs['query'] ?? null)) {
            $q = (string) $inputs['query'];
            $resultsQuery->where(function ($qb) use ($q) {
                $qb->where('patients.first_name', 'like', '%'.$q.'%')
                    ->orWhere('patients.last_name', 'like', '%'.$q.'%');
                if (is_numeric($q)) {
                    $qb->orWhere('patients.id', (int) $q);
                }
            });
        }

        $from = (string) ($inputs['from'] ?? ($defaultToToday ? Carbon::today()->toDateString() : ''));
        $to = (string) ($inputs['to'] ?? ($defaultToToday ? Carbon::today()->toDateString() : ''));

        if ($from !== '') {
            $resultsQuery->whereDate('consultations.updated_at', '>=', $from);
        }
        if ($to !== '') {
            $resultsQuery->whereDate('consultations.updated_at', '<=', $to);
        }

        $resultsReady = $resultsQuery->limit($limit)->get();
        $resultIds = $resultsReady->pluck('id')->all();
        $diagnosisSummaryByConsultation = [];

        if (! empty($resultIds)) {
            $dxRows = DB::table('diagnosis_records')
                ->join('diagnosis_lookup', 'diagnosis_records.diagnosis_id', '=', 'diagnosis_lookup.id')
                ->whereIn('diagnosis_records.consultation_id', $resultIds)
                ->select('diagnosis_records.consultation_id', 'diagnosis_lookup.diagnosis_name')
                ->orderBy('diagnosis_records.id')
                ->get();

            foreach ($dxRows as $dxRow) {
                $diagnosisSummaryByConsultation[$dxRow->consultation_id][] = $dxRow->diagnosis_name;
            }
        }

        $resultsReady = $resultsReady->map(function ($row) use ($diagnosisSummaryByConsultation) {
            $names = $diagnosisSummaryByConsultation[$row->id] ?? [];
            $row->diagnosis_summary = $names ? implode(', ', $names) : null;

            return $row;
        });

        return [
            'resultsReady' => $resultsReady,
            'resultsReadyCount' => (clone $resultsQuery)->count(),
            'resultsFilters' => [
                'query' => (string) ($inputs['query'] ?? ''),
                'from' => $from,
                'to' => $to,
            ],
        ];
    }
}
