<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class MorbidityReportService
{
    /**
     * FHSIS-style morbidity query for the given month/year with sex, zone and age group filters.
     * Returns ['start' => Carbon, 'rows' => Collection, 'totalCases' => int, 'reportDate' => string].
     */
    public static function query(string|int $month, string|int $year, ?string $sex, $zone, string $ageGroup, ?User $user = null): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $query = DB::table('diagnosis_records')
            ->join('consultations', 'diagnosis_records.consultation_id', '=', 'consultations.id')
            ->join('diagnosis_lookup', 'diagnosis_records.diagnosis_id', '=', 'diagnosis_lookup.id')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->whereBetween('consultations.created_at', [$start, $end]);

        // Zone-assigned workers only see morbidity from their assigned zones.
        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('households.zone_id', $user->accessibleZoneIds());
        }

        // sex filter (map shorthand to DB values)
        if ($sex && $sex !== 'All') {
            $sexMap = $sex === 'M' ? 'Male' : ($sex === 'F' ? 'Female' : $sex);
            $query->where('patients.sex', $sexMap);
        }

        // zone filter (zones select value is zone id)
        if (! empty($zone)) {
            $query->where('zones.id', $zone);
        }

        // age group filters
        switch ($ageGroup) {
            case 'infant_0_6d':
                $query->whereRaw('DATEDIFF(consultations.created_at, patients.date_of_birth) BETWEEN 0 AND 6');
                break;
            case 'infant_7_28d':
                $query->whereRaw('DATEDIFF(consultations.created_at, patients.date_of_birth) BETWEEN 7 AND 28');
                break;
            case 'infant_29_11m':
                $query->whereRaw('DATEDIFF(consultations.created_at, patients.date_of_birth) >= 29')
                    ->whereRaw('TIMESTAMPDIFF(MONTH, patients.date_of_birth, consultations.created_at) < 12');
                break;
            case 'child_1_4':
                $query->whereRaw('TIMESTAMPDIFF(YEAR, patients.date_of_birth, consultations.created_at) BETWEEN 1 AND 4');
                break;
            case 'child_5_9':
                $query->whereRaw('TIMESTAMPDIFF(YEAR, patients.date_of_birth, consultations.created_at) BETWEEN 5 AND 9');
                break;
            case 'child_10_14':
                $query->whereRaw('TIMESTAMPDIFF(YEAR, patients.date_of_birth, consultations.created_at) BETWEEN 10 AND 14');
                break;
            default:
                // adults/elderly 5-year buckets handled by specific values like '15_19', '20_24', ... or '70_plus'
                if (preg_match('/^(\d{2})_(\d{2})$/', $ageGroup, $matches)) {
                    $low = (int) $matches[1];
                    $high = (int) $matches[2];
                    $query->whereRaw("TIMESTAMPDIFF(YEAR, patients.date_of_birth, consultations.created_at) BETWEEN {$low} AND {$high}");
                } elseif ($ageGroup === '70_plus') {
                    $query->whereRaw('TIMESTAMPDIFF(YEAR, patients.date_of_birth, consultations.created_at) >= 70');
                }
                break;
        }

        $rows = $query->select(
            'diagnosis_lookup.diagnosis_code',
            'diagnosis_lookup.diagnosis_name',
            'diagnosis_lookup.category',
            DB::raw('COUNT(*) as case_count')
        )
            ->groupBy('diagnosis_lookup.id', 'diagnosis_lookup.diagnosis_code', 'diagnosis_lookup.diagnosis_name', 'diagnosis_lookup.category')
            ->orderByDesc('case_count')
            ->get();

        return [
            'start' => $start,
            'rows' => $rows,
            'totalCases' => $rows->sum('case_count'),
            'reportDate' => $start->format('F Y'),
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
        $zoneLabel = 'All Zones';
        if (! empty($zone)) {
            $zoneNumber = DB::table('zones')->where('id', $zone)->value('zone_number');
            $zoneLabel = $zoneNumber ? "Zone {$zoneNumber}" : 'Selected Zone';
        }

        return $zoneLabel;
    }

    public static function sexLabel(?string $sex): string
    {
        return $sex === 'M' ? 'Male' : ($sex === 'F' ? 'Female' : 'All Sex');
    }

    public static function ageGroupLabel(string $ageGroup): string
    {
        $labels = [
            'all' => 'All ages',
            'infant_0_6d' => '0–6 days',
            'infant_7_28d' => '7–28 days',
            'infant_29_11m' => '29 days – 11 months',
            'child_1_4' => '1–4 years',
            'child_5_9' => '5–9 years',
            'child_10_14' => '10–14 years',
            '70_plus' => '≥ 70 years',
        ];

        $label = $labels[$ageGroup] ?? 'All ages';
        if (! array_key_exists($ageGroup, $labels) && preg_match('/^(\d{2})_(\d{2})$/', $ageGroup, $matches)) {
            $label = "{$matches[1]}–{$matches[2]} years";
        }

        return $label;
    }
}
