<?php

namespace App\Http\Controllers;

use App\Services\MorbidityReportService;
use App\Services\PdfService;
use Illuminate\Http\Request;

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

        $report = MorbidityReportService::query($month, $year, $sex, $zone, $ageGroup, auth()->user());

        return view('reports.morbidity', [
            'rows' => $report['rows'],
            'totalCases' => $report['totalCases'],
            'reportDate' => $report['reportDate'],
            'month' => (int) $month,
            'year' => (int) $year,
            'sex' => $sex,
            'zones' => MorbidityReportService::zones(auth()->user()),
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

        $report = MorbidityReportService::query($month, $year, $sex, $zone, $ageGroup, auth()->user());

        $pdf = $pdfService->generateMorbidityReport(
            $report['rows'],
            $report['totalCases'],
            $report['reportDate'],
            $month,
            $year,
            MorbidityReportService::sexLabel($sex),
            MorbidityReportService::zoneLabel($zone),
            MorbidityReportService::ageGroupLabel($ageGroup)
        );

        return $pdf->download("Morbidity_Report_Sta_Ana_{$month}_{$year}.pdf");
    }
}
