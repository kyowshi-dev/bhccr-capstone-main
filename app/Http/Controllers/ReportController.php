<?php

namespace App\Http\Controllers;

use App\Services\PdfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Reports landing: FHSIS report type selection and period.
     */
    public function index(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        return view('reports.index', [
            'month' => (int) $month,
            'year' => (int) $year,
        ]);
    }

    /**
     * FHSIS-style Morbidity Report: Leading causes (by diagnosis) for the given month/year.
     * Aligns with DOH FHSIS morbidity reporting (ICD code, diagnosis name, case count).
     */
    public function morbidity(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $sex = $request->input('sex', 'All');
        $zone = $request->input('zone', null);
        $ageGroup = $request->input('age_group', 'all');

        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        // list of zones for the filter select
        $zones = DB::table('zones')->orderBy('zone_number')->get();

        $query = DB::table('diagnosis_records')
            ->join('consultations', 'diagnosis_records.consultation_id', '=', 'consultations.id')
            ->join('diagnosis_lookup', 'diagnosis_records.diagnosis_id', '=', 'diagnosis_lookup.id')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->whereBetween('consultations.created_at', [$start, $end]);

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

        $totalCases = $rows->sum('case_count');
        $reportDate = $start->format('F Y');

        return view('reports.morbidity', [
            'rows' => $rows,
            'totalCases' => $totalCases,
            'reportDate' => $reportDate,
            'month' => (int) $month,
            'year' => (int) $year,
            'sex' => $sex,
            'zones' => $zones,
            'selectedZone' => $zone,
            'age_group' => $ageGroup,
        ]);
    }

    /**
     * Download FHSIS Morbidity Report as PDF
     */
    public function downloadMorbidityPdf(Request $request, PdfService $pdfService)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $sex = $request->input('sex', 'All');
        $zone = $request->input('zone', null);
        $ageGroup = $request->input('age_group', 'all');

        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $query = DB::table('diagnosis_records')
            ->join('consultations', 'diagnosis_records.consultation_id', '=', 'consultations.id')
            ->join('diagnosis_lookup', 'diagnosis_records.diagnosis_id', '=', 'diagnosis_lookup.id')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->whereBetween('consultations.created_at', [$start, $end]);

        if ($sex && $sex !== 'All') {
            $sexMap = $sex === 'M' ? 'Male' : ($sex === 'F' ? 'Female' : $sex);
            $query->where('patients.sex', $sexMap);
        }

        if (! empty($zone)) {
            $query->where('zones.id', $zone);
        }

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

        $totalCases = $rows->sum('case_count');
        $reportDate = $start->format('F Y');

        $sexLabel = $sex === 'M' ? 'Male' : ($sex === 'F' ? 'Female' : 'All Sex');

        $ageGroupLabels = [
            'all' => 'All ages',
            'infant_0_6d' => '0–6 days',
            'infant_7_28d' => '7–28 days',
            'infant_29_11m' => '29 days – 11 months',
            'child_1_4' => '1–4 years',
            'child_5_9' => '5–9 years',
            'child_10_14' => '10–14 years',
            '70_plus' => '≥ 70 years',
        ];

        $ageGroupLabel = $ageGroupLabels[$ageGroup] ?? 'All ages';
        if (! array_key_exists($ageGroup, $ageGroupLabels) && preg_match('/^(\d{2})_(\d{2})$/', $ageGroup, $matches)) {
            $ageGroupLabel = "{$matches[1]}–{$matches[2]} years";
        }

        $zoneLabel = 'All Zones';
        if (! empty($zone)) {
            $zoneNumber = DB::table('zones')->where('id', $zone)->value('zone_number');
            $zoneLabel = $zoneNumber ? "Zone {$zoneNumber}" : 'Selected Zone';
        }

        $pdf = $pdfService->generateMorbidityReport(
            $rows,
            $totalCases,
            $reportDate,
            $month,
            $year,
            $sexLabel,
            $zoneLabel,
            $ageGroupLabel
        );

        $filename = "Morbidity_Report_Sta_Ana_{$month}_{$year}.pdf";

        return $pdf->download($filename);
    }
}
