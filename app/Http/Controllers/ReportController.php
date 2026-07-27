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
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $rows = DB::table('diagnosis_records')
            ->join('consultations', 'diagnosis_records.consultation_id', '=', 'consultations.id')
            ->join('diagnosis_lookup', 'diagnosis_records.diagnosis_id', '=', 'diagnosis_lookup.id')
            ->whereBetween('consultations.created_at', [$start, $end])
            ->select(
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
        ]);
    }

    /**
     * FHSIS-style Consultation Summary (Monthly Consolidation Table concept):
     * Total consultations and by status for the given month/year.
     */
    public function consultationSummary(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $total = DB::table('consultations')
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $byNature = DB::table('consultations')
            ->whereBetween('created_at', [$start, $end])
            ->select('nature_of_visit', DB::raw('COUNT(*) as count'))
            ->groupBy('nature_of_visit')
            ->get()
            ->keyBy('nature_of_visit');

        $prenatalCount = (int) ($byNature['Prenatal']->count ?? 0);
        $immunizationCount = (int) ($byNature['Immunization']->count ?? 0);

        $postpartumCount = 0;
        $familyPlanningCount = 0;

        $generalCount = max(0, $total - $prenatalCount - $immunizationCount - $postpartumCount - $familyPlanningCount);

        $programs = collect([
            [
                'key' => 'general',
                'label' => 'General Consultation',
                'description' => 'Includes all walk-in and scheduled visits, plus non-specialized consultations.',
                'count' => $generalCount,
            ],
            [
                'key' => 'prenatal',
                'label' => 'Prenatal Care',
                'description' => 'All prenatal consults and scheduled antenatal checkups.',
                'count' => $prenatalCount,
            ],
            [
                'key' => 'postpartum',
                'label' => 'Postpartum Care',
                'description' => 'Postpartum follow-up and checkups after delivery.',
                'count' => $postpartumCount,
            ],
            [
                'key' => 'immunization',
                'label' => 'Immunization',
                'description' => 'Routine and catch-up immunization services.',
                'count' => $immunizationCount,
            ],
            [
                'key' => 'family_planning',
                'label' => 'Family Planning',
                'description' => 'Counseling and family planning services.',
                'count' => $familyPlanningCount,
            ],
        ]);

        $previousStart = $start->copy()->subMonth()->startOfDay();
        $previousEnd = $previousStart->copy()->endOfMonth();

        $previousTotal = DB::table('consultations')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        $growthPercent = null;

        if ($previousTotal > 0) {
            $growthPercent = (($total - $previousTotal) / $previousTotal) * 100;
        }

        $reportDate = $start->format('F Y');

        return view('reports.consultation_summary', [
            'total' => $total,
            'programs' => $programs,
            'reportDate' => $reportDate,
            'month' => (int) $month,
            'year' => (int) $year,
            'growthPercent' => $growthPercent,
        ]);
    }

    /**
     * Download FHSIS Morbidity Report as PDF
     */
    public function downloadMorbidityPdf(Request $request, PdfService $pdfService)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $rows = DB::table('diagnosis_records')
            ->join('consultations', 'diagnosis_records.consultation_id', '=', 'consultations.id')
            ->join('diagnosis_lookup', 'diagnosis_records.diagnosis_id', '=', 'diagnosis_lookup.id')
            ->whereBetween('consultations.created_at', [$start, $end])
            ->select(
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

        $pdf = $pdfService->generateMorbidityReport($rows, $totalCases, $reportDate, $month, $year);

        $filename = "Morbidity_Report_Sta_Ana_{$month}_{$year}.pdf";

        return $pdf->download($filename);
    }
}
