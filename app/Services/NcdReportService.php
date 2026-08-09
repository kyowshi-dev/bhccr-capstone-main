<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class NcdReportService
{
    /**
     * ICD-10 code prefixes that define each NCD condition group.
     *
     * @var array<string, array{label: non-empty-string, codes: list<string>}>
     */
    public const CONDITIONS = [
        'hypertension' => [
            'label' => 'Hypertension',
            'codes' => ['I10', 'I11', 'I12', 'I13', 'I14', 'I15'],
        ],
        'diabetes' => [
            'label' => 'Diabetes Mellitus',
            'codes' => ['E10', 'E11', 'E12', 'E13', 'E14'],
        ],
    ];

    /**
     * FHSIS-style adult care / NCD report for the given month/year.
     *
     * For each condition group (hypertension, diabetes): distinct patients
     * seen in the period (with the diagnosis), number of consultations in
     * the period carrying the diagnosis, and total distinct patients on
     * the registry for the condition.
     * Returns an array keyed by report field (start, reportDate, rows,
     * totalPatients, totalConsultations).
     */
    public static function query(string|int $month, string|int $year, $zone = null, ?User $user = null): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $allowed = self::accessiblePatientQuery($zone, $user);

        $rows = collect(self::CONDITIONS)->map(function (array $condition) use ($start, $end, $allowed) {
            $periodQuery = self::diagnosisQuery($condition['codes'])
                ->whereIn('patients.id', $allowed)
                ->whereBetween('consultations.created_at', [$start, $end]);

            $patientsSeen = (clone $periodQuery)->distinct()->count('patients.id');
            $consultations = (clone $periodQuery)->count();

            $registryQuery = self::diagnosisQuery($condition['codes'])
                ->whereIn('patients.id', $allowed)
                ->where('consultations.created_at', '<=', $end);

            $registryPatients = $registryQuery->distinct()->count('patients.id');

            return (object) [
                'key' => $condition['label'],
                'label' => $condition['label'],
                'patients_seen' => (int) $patientsSeen,
                'consultations' => (int) $consultations,
                'registry_patients' => (int) $registryPatients,
            ];
        });

        return [
            'start' => $start,
            'reportDate' => $start->format('F Y'),
            'rows' => $rows,
            'totalPatients' => (int) $rows->sum('patients_seen'),
            'totalConsultations' => (int) $rows->sum('consultations'),
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
     * @param  list<string>  $codes
     * @return Builder
     */
    private static function diagnosisQuery(array $codes)
    {
        return DB::table('diagnosis_records')
            ->join('diagnosis_lookup', 'diagnosis_records.diagnosis_id', '=', 'diagnosis_lookup.id')
            ->join('consultations', 'diagnosis_records.consultation_id', '=', 'consultations.id')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->whereIn('diagnosis_lookup.diagnosis_code', $codes);
    }

    /**
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
