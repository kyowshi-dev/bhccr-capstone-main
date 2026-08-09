<?php

namespace App\Services;

use App\Models\OutwardReferral;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ReferralReportService
{
    /**
     * FHSIS-style referral report for the given month/year.
     *
     * Outward referrals broken down by destination facility (with status
     * counts) plus incoming referrals by source facility.
     * Returns an array keyed by report field (start, reportDate,
     * outwardByDestination, outwardByStatus, totalOutward,
     * inwardBySource, totalInward).
     */
    public static function query(string|int $month, string|int $year, $zone = null, ?User $user = null): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $outwardQuery = DB::table('outward_referrals')
            ->join('consultations', 'outward_referrals.consultation_id', '=', 'consultations.id')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->whereBetween('outward_referrals.created_at', [$start, $end]);

        if ($user !== null && $user->isZoneScoped()) {
            $outwardQuery->whereIn('zones.id', $user->accessibleZoneIds());
        }

        if (! empty($zone)) {
            $outwardQuery->where('zones.id', $zone);
        }

        $outwardByDestination = (clone $outwardQuery)
            ->select(
                'outward_referrals.destination_facility as destination',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN outward_referrals.status = \''.OutwardReferral::STATUS_PENDING.'\' THEN 1 ELSE 0 END) as pending'),
                DB::raw('SUM(CASE WHEN outward_referrals.status = \''.OutwardReferral::STATUS_COMPLETED.'\' THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN outward_referrals.status = \''.OutwardReferral::STATUS_NO_SHOW.'\' THEN 1 ELSE 0 END) as no_shows'),
                DB::raw('SUM(CASE WHEN outward_referrals.status = \''.OutwardReferral::STATUS_CANCELLED.'\' THEN 1 ELSE 0 END) as cancelled')
            )
            ->groupBy('outward_referrals.destination_facility')
            ->orderByDesc('total')
            ->get();

        $outwardByStatus = (clone $outwardQuery)
            ->select('outward_referrals.status as status', DB::raw('COUNT(*) as total'))
            ->groupBy('outward_referrals.status')
            ->get();

        $inwardBySource = DB::table('consultations')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->where('consultations.referred_from', '!=', '')
            ->whereNotNull('consultations.referred_from')
            ->whereBetween('consultations.created_at', [$start, $end])
            ->when($user !== null && $user->isZoneScoped(), fn ($q) => $q->whereIn('zones.id', $user->accessibleZoneIds()))
            ->when(! empty($zone), fn ($q) => $q->where('zones.id', $zone))
            ->select('consultations.referred_from as source', DB::raw('COUNT(*) as total'))
            ->groupBy('consultations.referred_from')
            ->orderByDesc('total')
            ->get();

        return [
            'start' => $start,
            'reportDate' => $start->format('F Y'),
            'outwardByDestination' => $outwardByDestination,
            'outwardByStatus' => $outwardByStatus,
            'totalOutward' => (int) $outwardByDestination->sum('total'),
            'inwardBySource' => $inwardBySource,
            'totalInward' => (int) $inwardBySource->sum('total'),
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

    public static function statusLabel(string $status): string
    {
        return OutwardReferral::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
}
