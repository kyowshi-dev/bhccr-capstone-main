<?php

namespace App\Services;

use App\Models\FamilyPlanningClient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class FamilyPlanningReportService
{
    /**
     * FHSIS-style family planning report for the given month/year.
     *
     * Rows are per contraceptive method with new acceptors, continuing
     * users, drop-outs and visits conducted during the period.
     * Returns an array keyed by report field (start, reportDate, rows,
     * totalNew, totalContinuing, totalDropOuts, totalVisits, totalClients).
     */
    public static function query(string|int $month, string|int $year, $zone = null, ?User $user = null): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $allowed = self::accessibleClientQuery($zone, $user);

        $clientsQuery = DB::table('family_planning_clients')
            ->join('patients', 'family_planning_clients.patient_id', '=', 'patients.id')
            ->whereIn('patients.id', $allowed)
            ->whereBetween('family_planning_clients.created_at', [$start, $end]);

        $byMethod = (clone $clientsQuery)
            ->select(
                'family_planning_clients.method as method',
                DB::raw('SUM(CASE WHEN family_planning_clients.type_of_client = \'new_acceptor\' THEN 1 ELSE 0 END) as new_acceptors'),
                DB::raw('SUM(CASE WHEN family_planning_clients.type_of_client = \'continuing_user\' THEN 1 ELSE 0 END) as continuing_users'),
                DB::raw('SUM(CASE WHEN family_planning_clients.type_of_client = \'drop_out\' THEN 1 ELSE 0 END) as drop_outs')
            )
            ->groupBy('family_planning_clients.method')
            ->get();

        $visitsByMethod = DB::table('family_planning_visits')
            ->join('family_planning_clients', 'family_planning_visits.client_id', '=', 'family_planning_clients.id')
            ->join('patients', 'family_planning_clients.patient_id', '=', 'patients.id')
            ->whereIn('patients.id', $allowed)
            ->whereBetween('family_planning_visits.visit_date', [$start->toDateString(), $end->toDateString()])
            ->select('family_planning_visits.method as method', DB::raw('COUNT(*) as visits'))
            ->groupBy('family_planning_visits.method')
            ->get();

        $visitsByMethod = $visitsByMethod->keyBy('method');

        $rows = $byMethod->map(function ($row) use ($visitsByMethod) {
            $row->visits = (int) ($visitsByMethod[$row->method]->visits ?? 0);
            $row->total = (int) $row->new_acceptors + (int) $row->continuing_users + (int) $row->drop_outs;

            return $row;
        });

        $allMethods = array_merge(
            FamilyPlanningClient::METHODS,
            $rows->pluck('method')->filter()->all()
        );

        $rows = collect(array_values(array_unique($allMethods)))
            ->map(function (string $method) use ($rows) {
                $existing = $rows->firstWhere('method', $method);

                return (object) [
                    'method' => $method,
                    'new_acceptors' => (int) ($existing->new_acceptors ?? 0),
                    'continuing_users' => (int) ($existing->continuing_users ?? 0),
                    'drop_outs' => (int) ($existing->drop_outs ?? 0),
                    'visits' => (int) ($existing->visits ?? 0),
                    'total' => (int) ($existing->total ?? 0),
                ];
            });

        return [
            'start' => $start,
            'reportDate' => $start->format('F Y'),
            'rows' => $rows,
            'totalNew' => (int) $rows->sum('new_acceptors'),
            'totalContinuing' => (int) $rows->sum('continuing_users'),
            'totalDropOuts' => (int) $rows->sum('drop_outs'),
            'totalVisits' => (int) $rows->sum('visits'),
            'totalClients' => (int) $rows->sum('total'),
        ];
    }

    public static function zones(?User $user = null): Collection
    {
        $query = DB::table('zones')->orderBy('zone_number');

        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('id', $user->accessibleZoneIds());
        }

        return $query->get();
    }

    public static function zoneLabel(?string $zone): string
    {
        if (empty($zone)) {
            return 'All Zones';
        }

        $zoneNumber = DB::table('zones')->where('id', $zone)->value('zone_number');

        return $zoneNumber ? "Zone {$zoneNumber}" : 'Selected Zone';
    }

    /**
     * @return Builder
     */
    private static function accessibleClientQuery($zone, ?User $user)
    {
        $query = DB::table('patients')
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->select('patients.id');

        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('zones.id', $user->accessibleZoneIds());
        }

        if (! empty($zone)) {
            $query->where('zones.id', $zone);
        }

        return $query;
    }
}
