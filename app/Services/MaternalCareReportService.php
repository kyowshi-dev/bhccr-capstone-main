<?php

namespace App\Services;

use App\Models\PostnatalRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class MaternalCareReportService
{
    /**
     * FHSIS-style maternal care indicators for the given month/year.
     *
     * Returns an array keyed by indicator name (start, reportDate,
     * newPrenatalClients, prenatalVisits, prenatalFourPlus, ttDoses,
     * ironSupplemented, syphilisPositive, totalDeliveries,
     * deliveriesByPlace, deliveriesByAttendant, deliveriesByOutcome,
     * postpartum24h, postpartum7d, postpartum14d, postpartum28d).
     */
    public static function query(string|int $month, string|int $year, $zone = null, ?User $user = null): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

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
            ->selectRaw('SUM(CASE WHEN pregnancies.syphilis_result = \'positive\' THEN 1 ELSE 0 END) as syphilis_positive')
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
            ->selectRaw('SUM(CASE WHEN postnatal_records.postpartum_24h_date IS NOT NULL THEN 1 ELSE 0 END) as v24h')
            ->selectRaw('SUM(CASE WHEN postnatal_records.postpartum_7d_date IS NOT NULL THEN 1 ELSE 0 END) as v7d')
            ->selectRaw('SUM(CASE WHEN postnatal_records.postpartum_14d_date IS NOT NULL THEN 1 ELSE 0 END) as v14d')
            ->selectRaw('SUM(CASE WHEN postnatal_records.postpartum_28d_date IS NOT NULL THEN 1 ELSE 0 END) as v28d')
            ->first();

        return [
            'start' => $start,
            'reportDate' => $start->format('F Y'),
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

    /**
     * Patient ids visible to this report, honoring zone-scoped users and the
     * explicit zone filter.
     *
     * @return Builder
     */
    private static function accessiblePatientQuery($zone, ?User $user)
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
