<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\User;
use App\Models\Vaccine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ImmunizationReportService
{
    /**
     * FHSIS-style EPI report for the given month/year.
     *
     * Returns doses given per antigen (by dose number) for the period, plus
     * the number of Fully Immunized Children (FIC): enrolled infants who
     * completed every Child-category vaccine series by the end of the period.
     * Returns an array keyed by report field (start, reportDate, doses,
     * fullyImmunizedChildren, childDoses, adultDoses, totalDoses).
     */
    public static function query(string|int $month, string|int $year, $zone = null, ?User $user = null): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $dosesQuery = DB::table('immunization_records')
            ->join('vaccines_lookup', 'immunization_records.vaccine_id', '=', 'vaccines_lookup.id')
            ->join('patients', 'immunization_records.patient_id', '=', 'patients.id')
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->where('immunization_records.no_show', false)
            ->whereBetween('immunization_records.date_given', [$start->toDateString(), $end->toDateString()]);

        if ($user !== null && $user->isZoneScoped()) {
            $dosesQuery->whereIn('zones.id', $user->accessibleZoneIds());
        }

        if (! empty($zone)) {
            $dosesQuery->where('zones.id', $zone);
        }

        $doses = (clone $dosesQuery)
            ->select(
                'vaccines_lookup.id as vaccine_id',
                'vaccines_lookup.vaccine_code',
                'vaccines_lookup.vaccine_name',
                'vaccines_lookup.category',
                'immunization_records.dose_number',
                DB::raw('COUNT(*) as doses')
            )
            ->groupBy(
                'vaccines_lookup.id',
                'vaccines_lookup.vaccine_code',
                'vaccines_lookup.vaccine_name',
                'vaccines_lookup.category',
                'immunization_records.dose_number'
            )
            ->orderBy('vaccines_lookup.sort_order')
            ->orderBy('immunization_records.dose_number')
            ->get();

        $childDoses = (clone $dosesQuery)
            ->where('vaccines_lookup.category', 'Child')
            ->count();

        $adultDoses = (clone $dosesQuery)
            ->whereIn('vaccines_lookup.category', ['Adult', 'Both'])
            ->count();

        return [
            'start' => $start,
            'reportDate' => $start->format('F Y'),
            'doses' => $doses,
            'fullyImmunizedChildren' => self::fullyImmunizedChildren($zone, $user, $end),
            'childDoses' => (int) $childDoses,
            'adultDoses' => (int) $adultDoses,
            'totalDoses' => (int) $childDoses + (int) $adultDoses,
        ];
    }

    /**
     * Count enrolled children under 1 year old who completed every
     * Child-category vaccine series by the report period end.
     */
    public static function fullyImmunizedChildren($zone, ?User $user, ?Carbon $end = null): int
    {
        $periodEnd = $end ?? Carbon::now();

        $query = Patient::query()
            ->where('is_immunization_enrolled', true)
            ->where('date_of_birth', '>', $periodEnd->copy()->subYear()->toDateString())
            ->where('date_of_birth', '<=', $periodEnd->toDateString())
            ->whereHas('household')
            ->with('immunizationRecords');

        if ($user !== null && $user->isZoneScoped()) {
            $query->whereHas('household', fn ($q) => $q->whereIn('zone_id', $user->accessibleZoneIds()));
        }

        if (! empty($zone)) {
            $query->whereHas('household', fn ($q) => $q->where('zone_id', $zone));
        }

        $service = app(ChildImmunizationService::class);
        $childVaccines = Vaccine::where('category', 'Child')->with('schedules')->get();

        $count = 0;

        foreach ($query->get() as $child) {
            $complete = $childVaccines->every(function (Vaccine $vaccine) use ($service, $child) {
                return $service->statusFor($child, $vaccine) === ChildImmunizationService::STATUS_COMPLETED;
            });

            if ($complete) {
                $count++;
            }
        }

        return $count;
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
}
