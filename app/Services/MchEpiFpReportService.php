<?php

namespace App\Services;

use App\Helpers\PatientCode;
use App\Models\FamilyPlanningClient;
use App\Models\PostnatalRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

final class MchEpiFpReportService
{
    public const PROGRAM_MATERNAL = 'maternal';

    public const PROGRAM_EPI = 'epi';

    public const PROGRAM_FP = 'fp';

    public const PROGRAMS = [
        self::PROGRAM_MATERNAL => 'Maternal Care',
        self::PROGRAM_EPI => 'EPI Immunization',
        self::PROGRAM_FP => 'Family Planning',
    ];

    public const PROGRAM_ORDER = [
        self::PROGRAM_MATERNAL => 0,
        self::PROGRAM_EPI => 1,
        self::PROGRAM_FP => 2,
    ];

    public const DEFAULT_PER_PAGE = 50;

    public static function defaultFilters(): array
    {
        return [
            'from' => Carbon::now()->startOfMonth()->toDateString(),
            'to' => Carbon::now()->endOfMonth()->toDateString(),
            'zone' => null,
            'program' => 'all',
            'search' => '',
            'page' => 1,
            'perPage' => self::DEFAULT_PER_PAGE,
        ];
    }

    public static function normalizeFilters(array $input): array
    {
        $defaults = self::defaultFilters();

        $from = self::parseDate($input['from'] ?? null) ?? $defaults['from'];
        $to = self::parseDate($input['to'] ?? null) ?? $defaults['to'];

        if (Carbon::parse($from)->gt(Carbon::parse($to))) {
            [$from, $to] = [$to, $from];
        }

        $program = isset($input['program']) && array_key_exists($input['program'], self::PROGRAMS)
            ? (string) $input['program']
            : 'all';

        return [
            'from' => $from,
            'to' => $to,
            'zone' => ! empty($input['zone']) ? (string) $input['zone'] : null,
            'program' => $program,
            'search' => trim((string) ($input['search'] ?? '')),
            'page' => max(1, (int) ($input['page'] ?? $defaults['page'])),
            'perPage' => max(1, (int) ($input['perPage'] ?? self::DEFAULT_PER_PAGE)),
        ];
    }

    /**
     * Build the merged Maternal / EPI / Family Planning report for the given
     * filters. Returns program summaries plus a unified patient-level
     * register of service events (date, patient, accountable health worker).
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public static function query(array $filters, ?User $user = null, bool $paginate = true): array
    {
        $filters = self::normalizeFilters($filters);

        $start = Carbon::parse($filters['from'])->startOfDay();
        $end = Carbon::parse($filters['to'])->endOfDay();

        $zone = $filters['zone'];
        $program = $filters['program'];
        $search = $filters['search'];

        $rows = collect();

        if (self::wantsProgram($program, self::PROGRAM_MATERNAL)) {
            $rows = $rows->concat(self::maternalRows($start, $end, $zone, $search, $user));
        }

        if (self::wantsProgram($program, self::PROGRAM_EPI)) {
            $rows = $rows->concat(self::epiRows($start, $end, $zone, $search, $user));
        }

        if (self::wantsProgram($program, self::PROGRAM_FP)) {
            $rows = $rows->concat(self::fpRows($start, $end, $zone, $search, $user));
        }

        $rows = $rows->sortBy([
            ['date', 'asc'],
            ['program_order', 'asc'],
            ['patient_name', 'asc'],
        ])->values();

        $totalRows = $rows->count();

        $programCounts = [
            self::PROGRAM_MATERNAL => (int) $rows->where('program', self::PROGRAM_MATERNAL)->count(),
            self::PROGRAM_EPI => (int) $rows->where('program', self::PROGRAM_EPI)->count(),
            self::PROGRAM_FP => (int) $rows->where('program', self::PROGRAM_FP)->count(),
        ];

        return [
            'start' => $start,
            'end' => $end,
            'reportDate' => $start->format('m/d/Y').' - '.$end->format('m/d/Y'),
            'filters' => $filters,
            'programCounts' => $programCounts,
            'summaries' => [
                'maternal' => self::wantsProgram($program, self::PROGRAM_MATERNAL)
                    ? self::maternalSummaries($start, $end, $zone, $user)
                    : null,
                'epi' => self::wantsProgram($program, self::PROGRAM_EPI)
                    ? self::epiSummaries($start, $end, $zone, $user)
                    : null,
                'fp' => self::wantsProgram($program, self::PROGRAM_FP)
                    ? self::fpSummaries($start, $end, $zone, $user)
                    : null,
            ],
            'rows' => $paginate
                ? self::paginate($rows, $filters, $totalRows)
                : $rows,
            'totalRows' => $totalRows,
        ];
    }

    // ------------------------------------------------------------------
    // Register rows
    // ------------------------------------------------------------------

    /**
     * @return Collection<int, object>
     */
    private static function maternalRows(Carbon $start, Carbon $end, ?string $zone, string $search, ?User $user): Collection
    {
        $from = $start->toDateString();
        $to = $end->toDateString();

        $rows = collect();

        $patientColumns = self::patientColumns();
        $workerColumns = self::workerColumns('health_workers');

        $scope = fn (Builder $query) => self::applyZoneScope($query, $zone, $user);

        $registrations = self::applySearch(
            $scope(
                DB::table('pregnancies')
                    ->join('patients', 'pregnancies.patient_id', '=', 'patients.id')
                    ->join('households', 'patients.household_id', '=', 'households.id')
                    ->join('zones', 'households.zone_id', '=', 'zones.id')
                    ->leftJoin('health_workers', 'pregnancies.recorded_by', '=', 'health_workers.id')
                    ->whereBetween('pregnancies.created_at', [$start, $end])
            ),
            $search
        )
            ->select(array_merge(
                $patientColumns,
                $workerColumns,
                ['zones.zone_number as zone', DB::raw('DATE(pregnancies.created_at) as date')]
            ))
            ->get();

        foreach ($registrations as $row) {
            $rows->push(self::registerRow(
                (string) $row->date,
                self::PROGRAM_MATERNAL,
                'Prenatal registration',
                $row
            ));
        }

        $visits = self::applySearch(
            $scope(
                DB::table('prenatal_visits')
                    ->join('pregnancies', 'prenatal_visits.pregnancy_id', '=', 'pregnancies.id')
                    ->join('patients', 'pregnancies.patient_id', '=', 'patients.id')
                    ->join('households', 'patients.household_id', '=', 'households.id')
                    ->join('zones', 'households.zone_id', '=', 'zones.id')
                    ->leftJoin('health_workers', 'prenatal_visits.recorded_by', '=', 'health_workers.id')
                    ->whereBetween('prenatal_visits.visit_date', [$from, $to])
            ),
            $search
        )
            ->select(array_merge(
                $patientColumns,
                $workerColumns,
                ['zones.zone_number as zone', 'prenatal_visits.visit_date as date']
            ))
            ->get();

        foreach ($visits as $row) {
            $rows->push(self::registerRow(
                (string) $row->date,
                self::PROGRAM_MATERNAL,
                'Prenatal visit',
                $row
            ));
        }

        $deliveries = self::applySearch(
            $scope(
                DB::table('postnatal_records')
                    ->join('patients', 'postnatal_records.patient_id', '=', 'patients.id')
                    ->join('households', 'patients.household_id', '=', 'households.id')
                    ->join('zones', 'households.zone_id', '=', 'zones.id')
                    ->leftJoin('health_workers', 'postnatal_records.recorded_by', '=', 'health_workers.id')
                    ->whereBetween('postnatal_records.delivery_date', [$from, $to])
            ),
            $search
        )
            ->select(array_merge(
                $patientColumns,
                $workerColumns,
                [
                    'zones.zone_number as zone',
                    'postnatal_records.delivery_date as date',
                    'postnatal_records.pregnancy_outcome',
                ]
            ))
            ->get();

        foreach ($deliveries as $row) {
            $rows->push(self::registerRow(
                (string) $row->date,
                self::PROGRAM_MATERNAL,
                'Delivery - '.self::outcomeLabel((string) $row->pregnancy_outcome),
                $row
            ));
        }

        $postnatal = self::applySearch(
            $scope(
                DB::table('postnatal_records')
                    ->join('patients', 'postnatal_records.patient_id', '=', 'patients.id')
                    ->join('households', 'patients.household_id', '=', 'households.id')
                    ->join('zones', 'households.zone_id', '=', 'zones.id')
                    ->leftJoin('health_workers', 'postnatal_records.recorded_by', '=', 'health_workers.id')
                    ->where(function (Builder $query) use ($from, $to) {
                        $query->whereBetween('postnatal_records.postpartum_24h_date', [$from, $to])
                            ->orWhereBetween('postnatal_records.postpartum_7d_date', [$from, $to])
                            ->orWhereBetween('postnatal_records.postpartum_14d_date', [$from, $to])
                            ->orWhereBetween('postnatal_records.postpartum_28d_date', [$from, $to]);
                    })
            ),
            $search
        )
            ->select(array_merge(
                $patientColumns,
                $workerColumns,
                [
                    'zones.zone_number as zone',
                    'postnatal_records.postpartum_24h_date',
                    'postnatal_records.postpartum_7d_date',
                    'postnatal_records.postpartum_14d_date',
                    'postnatal_records.postpartum_28d_date',
                ]
            ))
            ->get();

        foreach ($postnatal as $row) {
            foreach (PostnatalRecord::POSTPARTUM_SLOTS as $column => $days) {
                $date = $row->{$column};

                if ($date === null) {
                    continue;
                }

                $date = Carbon::parse($date);

                if ($date->lt($start->copy()->startOfDay()) || $date->gt($end->copy()->endOfDay())) {
                    continue;
                }

                $label = $days === 1 ? 'Postnatal visit (within 24 hours)' : "Postnatal visit (within {$days} days)";

                $rows->push(self::registerRow(
                    $date->toDateString(),
                    self::PROGRAM_MATERNAL,
                    $label,
                    $row
                ));
            }
        }

        return $rows;
    }

    /**
     * @return Collection<int, object>
     */
    private static function epiRows(Carbon $start, Carbon $end, ?string $zone, string $search, ?User $user): Collection
    {
        $rows = collect();

        $records = self::applySearch(
            self::applyZoneScope(
                DB::table('immunization_records')
                    ->join('vaccines_lookup', 'immunization_records.vaccine_id', '=', 'vaccines_lookup.id')
                    ->join('patients', 'immunization_records.patient_id', '=', 'patients.id')
                    ->join('households', 'patients.household_id', '=', 'households.id')
                    ->join('zones', 'households.zone_id', '=', 'zones.id')
                    ->leftJoin('health_workers', 'immunization_records.administered_by', '=', 'health_workers.id')
                    ->where('immunization_records.no_show', false)
                    ->whereBetween('immunization_records.date_given', [$start->toDateString(), $end->toDateString()]),
                $zone,
                $user
            ),
            $search
        )
            ->select(array_merge(
                self::patientColumns(),
                self::workerColumns('health_workers'),
                [
                    'zones.zone_number as zone',
                    'immunization_records.date_given as date',
                    'vaccines_lookup.vaccine_name',
                    'immunization_records.dose_number',
                ]
            ))
            ->get();

        foreach ($records as $row) {
            $rows->push(self::registerRow(
                (string) $row->date,
                self::PROGRAM_EPI,
                "{$row->vaccine_name} {$row->dose_number}",
                $row
            ));
        }

        return $rows;
    }

    /**
     * @return Collection<int, object>
     */
    private static function fpRows(Carbon $start, Carbon $end, ?string $zone, string $search, ?User $user): Collection
    {
        $from = $start->toDateString();
        $to = $end->toDateString();

        $rows = collect();

        $patientColumns = self::patientColumns();
        $workerColumns = self::workerColumns('health_workers');

        $clients = self::applySearch(
            self::applyZoneScope(
                DB::table('family_planning_clients')
                    ->join('patients', 'family_planning_clients.patient_id', '=', 'patients.id')
                    ->join('households', 'patients.household_id', '=', 'households.id')
                    ->join('zones', 'households.zone_id', '=', 'zones.id')
                    ->leftJoin('health_workers', 'family_planning_clients.recorded_by', '=', 'health_workers.id')
                    ->whereBetween('family_planning_clients.created_at', [$start, $end]),
                $zone,
                $user
            ),
            $search
        )
            ->select(array_merge(
                $patientColumns,
                $workerColumns,
                [
                    'zones.zone_number as zone',
                    DB::raw('DATE(family_planning_clients.created_at) as date'),
                    'family_planning_clients.type_of_client',
                    'family_planning_clients.method',
                ]
            ))
            ->get();

        foreach ($clients as $row) {
            $rows->push(self::registerRow(
                (string) $row->date,
                self::PROGRAM_FP,
                self::fpTypeLabel((string) $row->type_of_client).' - '.$row->method,
                $row
            ));
        }

        $visits = self::applySearch(
            self::applyZoneScope(
                DB::table('family_planning_visits')
                    ->join('family_planning_clients', 'family_planning_visits.client_id', '=', 'family_planning_clients.id')
                    ->join('patients', 'family_planning_clients.patient_id', '=', 'patients.id')
                    ->join('households', 'patients.household_id', '=', 'households.id')
                    ->join('zones', 'households.zone_id', '=', 'zones.id')
                    ->leftJoin('health_workers', 'family_planning_visits.recorded_by', '=', 'health_workers.id')
                    ->whereBetween('family_planning_visits.visit_date', [$from, $to]),
                $zone,
                $user
            ),
            $search
        )
            ->select(array_merge(
                $patientColumns,
                $workerColumns,
                [
                    'zones.zone_number as zone',
                    'family_planning_visits.visit_date as date',
                    'family_planning_visits.method',
                ]
            ))
            ->get();

        foreach ($visits as $row) {
            $rows->push(self::registerRow(
                (string) $row->date,
                self::PROGRAM_FP,
                'Visit - '.($row->method ?? 'Family Planning'),
                $row
            ));
        }

        return $rows;
    }

    /**
     * @param  object  $row
     */
    private static function registerRow(string $date, string $program, string $service, $row): object
    {
        return (object) [
            'date' => $date,
            'program' => $program,
            'program_order' => self::PROGRAM_ORDER[$program],
            'program_label' => self::PROGRAMS[$program],
            'service' => $service,
            'patient_id' => (int) $row->patient_id,
            'patient_code' => PatientCode::format((int) $row->patient_id),
            'patient_name' => fullName($row->last_name, $row->first_name, $row->middle_name ?? null, $row->suffix ?? null),
            'zone' => $row->zone,
            'worker_name' => self::workerName($row->worker_first_name ?? null, $row->worker_last_name ?? null),
        ];
    }

    /**
     * @return list<string>
     */
    private static function patientColumns(): array
    {
        return [
            'patients.id as patient_id',
            'patients.last_name',
            'patients.first_name',
            'patients.middle_name',
            'patients.suffix',
        ];
    }

    /**
     * @return list<string>
     */
    private static function workerColumns(string $alias): array
    {
        return [
            "{$alias}.first_name as worker_first_name",
            "{$alias}.last_name as worker_last_name",
        ];
    }

    private static function workerName(?string $first, ?string $last): ?string
    {
        $name = trim(($first ?? '').' '.($last ?? ''));

        return $name !== '' ? $name : null;
    }

    // ------------------------------------------------------------------
    // Summaries
    // ------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private static function maternalSummaries(Carbon $start, Carbon $end, ?string $zone, ?User $user): array
    {
        $allowed = self::accessiblePatientQuery($zone, $user);

        $newPrenatalClients = DB::table('pregnancies')
            ->join('patients', 'pregnancies.patient_id', '=', 'patients.id')
            ->whereIn('patients.id', $allowed)
            ->whereBetween('pregnancies.created_at', [$start, $end])
            ->count();

        $prenatalVisits = DB::table('prenatal_visits')
            ->join('pregnancies', 'prenatal_visits.pregnancy_id', '=', 'pregnancies.id')
            ->join('patients', 'pregnancies.patient_id', '=', 'patients.id')
            ->whereIn('patients.id', $allowed)
            ->whereBetween('prenatal_visits.visit_date', [$start->toDateString(), $end->toDateString()])
            ->count();

        $prenatalFourPlus = DB::table('prenatal_visits')
            ->join('pregnancies', 'prenatal_visits.pregnancy_id', '=', 'pregnancies.id')
            ->join('patients', 'pregnancies.patient_id', '=', 'patients.id')
            ->whereIn('patients.id', $allowed)
            ->where('prenatal_visits.visit_date', '<=', $end->toDateString())
            ->groupBy('pregnancies.id')
            ->havingRaw('COUNT(prenatal_visits.id) >= 4')
            ->count(DB::raw('1'));

        $pregnancyStats = DB::table('pregnancies')
            ->join('patients', 'pregnancies.patient_id', '=', 'patients.id')
            ->whereIn('patients.id', $allowed)
            ->whereBetween('pregnancies.created_at', [$start, $end])
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN pregnancies.tt_date IS NOT NULL THEN 1 ELSE 0 END) as tt_doses')
            ->selectRaw('SUM(CASE WHEN pregnancies.iron_taken = 1 THEN 1 ELSE 0 END) as iron_supplemented')
            ->selectRaw("SUM(CASE WHEN pregnancies.syphilis_result = 'positive' THEN 1 ELSE 0 END) as syphilis_positive")
            ->first();

        $deliveriesQuery = DB::table('postnatal_records')
            ->join('patients', 'postnatal_records.patient_id', '=', 'patients.id')
            ->whereIn('patients.id', $allowed)
            ->whereBetween('postnatal_records.delivery_date', [$start->toDateString(), $end->toDateString()]);

        $totalDeliveries = (clone $deliveriesQuery)->count();

        $deliveriesByPlace = (clone $deliveriesQuery)
            ->select('postnatal_records.place_delivered as key', DB::raw('COUNT(*) as total'))
            ->groupBy('postnatal_records.place_delivered')
            ->get();

        $deliveriesByAttendant = (clone $deliveriesQuery)
            ->select('postnatal_records.attendant_at_birth as key', DB::raw('COUNT(*) as total'))
            ->groupBy('postnatal_records.attendant_at_birth')
            ->get();

        $deliveriesByOutcome = (clone $deliveriesQuery)
            ->select('postnatal_records.pregnancy_outcome as key', DB::raw('COUNT(*) as total'))
            ->groupBy('postnatal_records.pregnancy_outcome')
            ->get();

        $postpartum = DB::table('postnatal_records')
            ->join('patients', 'postnatal_records.patient_id', '=', 'patients.id')
            ->whereIn('patients.id', $allowed)
            ->selectRaw('SUM(CASE WHEN postnatal_records.postpartum_24h_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as v24h', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('SUM(CASE WHEN postnatal_records.postpartum_7d_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as v7d', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('SUM(CASE WHEN postnatal_records.postpartum_14d_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as v14d', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('SUM(CASE WHEN postnatal_records.postpartum_28d_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as v28d', [$start->toDateString(), $end->toDateString()])
            ->first();

        return [
            'newPrenatalClients' => (int) $newPrenatalClients,
            'prenatalVisits' => (int) $prenatalVisits,
            'prenatalFourPlus' => (int) $prenatalFourPlus,
            'ttDoses' => (int) ($pregnancyStats->tt_doses ?? 0),
            'ironSupplemented' => (int) ($pregnancyStats->iron_supplemented ?? 0),
            'syphilisPositive' => (int) ($pregnancyStats->syphilis_positive ?? 0),
            'totalDeliveries' => (int) $totalDeliveries,
            'deliveriesByPlace' => $deliveriesByPlace,
            'deliveriesByAttendant' => $deliveriesByAttendant,
            'deliveriesByOutcome' => $deliveriesByOutcome,
            'postpartum24h' => (int) ($postpartum->v24h ?? 0),
            'postpartum7d' => (int) ($postpartum->v7d ?? 0),
            'postpartum14d' => (int) ($postpartum->v14d ?? 0),
            'postpartum28d' => (int) ($postpartum->v28d ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function epiSummaries(Carbon $start, Carbon $end, ?string $zone, ?User $user): array
    {
        $dosesQuery = DB::table('immunization_records')
            ->join('vaccines_lookup', 'immunization_records.vaccine_id', '=', 'vaccines_lookup.id')
            ->join('patients', 'immunization_records.patient_id', '=', 'patients.id')
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->where('immunization_records.no_show', false)
            ->whereBetween('immunization_records.date_given', [$start->toDateString(), $end->toDateString()]);

        self::applyZoneScope($dosesQuery, $zone, $user);

        $dosesByVaccine = (clone $dosesQuery)
            ->select(
                'vaccines_lookup.vaccine_name',
                'vaccines_lookup.category',
                'immunization_records.dose_number',
                DB::raw('COUNT(*) as doses')
            )
            ->groupBy(
                'vaccines_lookup.vaccine_name',
                'vaccines_lookup.category',
                'immunization_records.dose_number'
            )
            ->orderBy('vaccines_lookup.vaccine_name')
            ->orderBy('immunization_records.dose_number')
            ->get();

        $childDoses = (clone $dosesQuery)
            ->where('vaccines_lookup.category', 'Child')
            ->count();

        $adultDoses = (clone $dosesQuery)
            ->whereIn('vaccines_lookup.category', ['Adult', 'Both'])
            ->count();

        return [
            'dosesByVaccine' => $dosesByVaccine,
            'childDoses' => (int) $childDoses,
            'adultDoses' => (int) $adultDoses,
            'totalDoses' => (int) $childDoses + (int) $adultDoses,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function fpSummaries(Carbon $start, Carbon $end, ?string $zone, ?User $user): array
    {
        $allowed = self::accessibleClientQuery($zone, $user);

        $clientsQuery = DB::table('family_planning_clients')
            ->join('patients', 'family_planning_clients.patient_id', '=', 'patients.id')
            ->whereIn('patients.id', $allowed)
            ->whereBetween('family_planning_clients.created_at', [$start, $end]);

        $byMethod = (clone $clientsQuery)
            ->select(
                'family_planning_clients.method as method',
                DB::raw("SUM(CASE WHEN family_planning_clients.type_of_client = 'new_acceptor' THEN 1 ELSE 0 END) as new_acceptors"),
                DB::raw("SUM(CASE WHEN family_planning_clients.type_of_client = 'continuing_user' THEN 1 ELSE 0 END) as continuing_users"),
                DB::raw("SUM(CASE WHEN family_planning_clients.type_of_client = 'drop_out' THEN 1 ELSE 0 END) as drop_outs"),
                DB::raw("SUM(CASE WHEN family_planning_clients.type_of_client = 'others' THEN 1 ELSE 0 END) as others")
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
            ->get()
            ->keyBy('method');

        $rows = $byMethod->map(function ($row) use ($visitsByMethod) {
            $row->visits = (int) ($visitsByMethod[$row->method]->visits ?? 0);
            $row->total = (int) $row->new_acceptors + (int) $row->continuing_users + (int) $row->drop_outs + (int) $row->others;

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
                    'others' => (int) ($existing->others ?? 0),
                    'visits' => (int) ($existing->visits ?? 0),
                    'total' => (int) ($existing->total ?? 0),
                ];
            });

        return [
            'rows' => $rows,
            'totalNew' => (int) $rows->sum('new_acceptors'),
            'totalContinuing' => (int) $rows->sum('continuing_users'),
            'totalDropOuts' => (int) $rows->sum('drop_outs'),
            'totalOthers' => (int) $rows->sum('others'),
            'totalVisits' => (int) $rows->sum('visits'),
            'totalClients' => (int) $rows->sum('total'),
        ];
    }

    // ------------------------------------------------------------------
    // Shared query helpers
    // ------------------------------------------------------------------

    private static function wantsProgram(string $program, string $candidate): bool
    {
        return in_array($program, ['all', $candidate], true);
    }

    private static function applySearch(Builder $query, string $search): Builder
    {
        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($search) {
            $query->where('patients.last_name', 'like', "%{$search}%")
                ->orWhere('patients.first_name', 'like', "%{$search}%")
                ->orWhere('patients.middle_name', 'like', "%{$search}%");
        });
    }

    private static function applyZoneScope(Builder $query, ?string $zone, ?User $user): Builder
    {
        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('zones.id', $user->accessibleZoneIds());
        }

        if (! empty($zone)) {
            $query->where('zones.id', $zone);
        }

        return $query;
    }

    /**
     * Patient ids visible to this report, honoring zone-scoped users and the
     * explicit zone filter.
     */
    private static function accessiblePatientQuery(?string $zone, ?User $user): Builder
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

    private static function accessibleClientQuery(?string $zone, ?User $user): Builder
    {
        return self::accessiblePatientQuery($zone, $user);
    }

    private static function paginate(Collection $rows, array $filters, int $total): LengthAwarePaginator
    {
        $perPage = $filters['perPage'];
        $page = $filters['page'];

        $items = $rows->forPage($page, $perPage)->values();

        return (new Paginator($items, $total, $perPage, $page, [
            'path' => route('reports.mch-epi-fp'),
        ]))->withQueryString();
    }

    // ------------------------------------------------------------------
    // Display helpers
    // ------------------------------------------------------------------

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

    public static function programLabel(string $program): string
    {
        return $program === 'all' ? 'All Programs' : (self::PROGRAMS[$program] ?? 'All Programs');
    }

    public static function placeLabel(string $key): string
    {
        return PostnatalRecord::PLACES[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    public static function attendantLabel(string $key): string
    {
        return PostnatalRecord::ATTENDANTS[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    public static function outcomeLabel(string $key): string
    {
        return PostnatalRecord::OUTCOMES[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    public static function fpTypeLabel(string $key): string
    {
        return FamilyPlanningClient::TYPES[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    private static function parseDate(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
