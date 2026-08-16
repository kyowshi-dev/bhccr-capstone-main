<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-side queries for the immunization module.
 */
final class ImmunizationQueryService
{
    public static function recentRecords(int $limit = 20): Collection
    {
        return DB::table('immunization_records')
            ->where('immunization_records.no_show', false)
            ->join('patients', 'immunization_records.patient_id', '=', 'patients.id')
            ->join('vaccines_lookup', 'immunization_records.vaccine_id', '=', 'vaccines_lookup.id')
            ->leftJoin('health_workers', 'immunization_records.administered_by', '=', 'health_workers.id')
            ->select(
                'immunization_records.id',
                'immunization_records.patient_id',
                'immunization_records.vaccine_id',
                'immunization_records.date_given',
                'immunization_records.dose_number',
                'patients.first_name',
                'patients.last_name',
                'vaccines_lookup.vaccine_name',
                DB::raw(dbConcat(['health_workers.first_name', 'health_workers.last_name']).' as worker_name')
            )
            ->orderByDesc('immunization_records.date_given')
            ->limit($limit)
            ->get();
    }

    /**
     * Infant (< 1 year) enrollment and coverage stats for the index dashboard.
     *
     * @return array{infantTotal: int, infantWithAnyDose: int, infantCoveragePercent: ?int}
     */
    public static function infantStats(?int $zoneId = null): array
    {
        $infantCutoff = Carbon::today()->subYear()->toDateString();

        $patientBase = DB::table('patients')->join('households', 'patients.household_id', '=', 'households.id');
        if ($zoneId !== null) {
            $patientBase->where('households.zone_id', $zoneId);
        }

        $infantTotal = (clone $patientBase)
            ->where('patients.date_of_birth', '>=', $infantCutoff)
            ->count();
        $infantWithAnyDose = (clone $patientBase)
            ->join('immunization_records', 'immunization_records.patient_id', '=', 'patients.id')
            ->where('immunization_records.no_show', false)
            ->where('patients.date_of_birth', '>=', $infantCutoff)
            ->distinct('immunization_records.patient_id')
            ->count('immunization_records.patient_id');

        return [
            'infantTotal' => $infantTotal,
            'infantWithAnyDose' => $infantWithAnyDose,
            'infantCoveragePercent' => $infantTotal > 0
                ? (int) round(($infantWithAnyDose / $infantTotal) * 100)
                : null,
        ];
    }

    /**
     * Adult (18+) enrollment and per-vaccine dose stats for the index dashboard.
     *
     * @return array{adultEnrolled: int, dosesByVaccine: Collection}
     */
    public static function adultStats(?int $zoneId = null): array
    {
        $adultCutoff = Carbon::today()->subYears(18)->toDateString();

        $patientBase = DB::table('patients')
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->where('patients.date_of_birth', '<=', $adultCutoff);

        if ($zoneId !== null) {
            $patientBase->where('households.zone_id', $zoneId);
        }

        $adultEnrolled = (clone $patientBase)
            ->where('patients.is_immunization_enrolled', true)
            ->count();

        $dosesByVaccine = DB::table('immunization_records')
            ->where('immunization_records.no_show', false)
            ->join('patients', 'immunization_records.patient_id', '=', 'patients.id')
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->join('vaccines_lookup', 'immunization_records.vaccine_id', '=', 'vaccines_lookup.id')
            ->whereIn('vaccines_lookup.category', ['Adult', 'Both'])
            ->where('patients.date_of_birth', '<=', $adultCutoff)
            ->when($zoneId !== null, fn ($query) => $query->where('households.zone_id', $zoneId))
            ->select(
                'vaccines_lookup.vaccine_name',
                DB::raw('COUNT(immunization_records.id) as doses_count')
            )
            ->groupBy('vaccines_lookup.id', 'vaccines_lookup.vaccine_name', 'vaccines_lookup.sort_order')
            ->orderBy('vaccines_lookup.sort_order')
            ->get();

        return [
            'adultEnrolled' => $adultEnrolled,
            'dosesByVaccine' => $dosesByVaccine,
        ];
    }

    public static function recordsForPatient(int $patientId): Collection
    {
        return DB::table('immunization_records')
            ->where('immunization_records.no_show', false)
            ->join('vaccines_lookup', 'immunization_records.vaccine_id', '=', 'vaccines_lookup.id')
            ->leftJoin('health_workers', 'immunization_records.administered_by', '=', 'health_workers.id')
            ->where('immunization_records.patient_id', $patientId)
            ->select(
                'immunization_records.*',
                'vaccines_lookup.vaccine_name',
                'vaccines_lookup.vaccine_code',
                DB::raw(dbConcat(['health_workers.first_name', 'health_workers.last_name']).' as administered_by_name')
            )
            ->orderByDesc('immunization_records.date_given')
            ->get();
    }

    /**
     * @param  list<string>  $categories
     */
    public static function vaccinesFor(array $categories): Collection
    {
        return DB::table('vaccines_lookup')
            ->whereIn('category', $categories)
            ->orderBy('sort_order')
            ->get();
    }

    public static function healthWorkers(): Collection
    {
        return DB::table('health_workers')
            ->orderBy('last_name')
            ->get();
    }
}
