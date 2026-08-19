<?php

namespace App\Http\Controllers;

use App\Services\MchEpiFpReportService;
use App\Services\MorbidityReportService;
use App\Services\PdfService;
use Carbon\Carbon;
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

    /**
     * Merged Maternal Care, EPI Immunization and Family Planning report:
     * program summaries plus a patient-level service register with the
     * accountable health worker and the specific service date for each row.
     */
    public function mchEpiFp(Request $request)
    {
        $filters = MchEpiFpReportService::normalizeFilters($request->only([
            'from', 'to', 'zone', 'program', 'search', 'page', 'per_page',
        ]));

        $report = MchEpiFpReportService::query($filters, auth()->user());

        return view('reports.mch_epi_fp', [
            'report' => $report,
            'filters' => $filters,
            'zones' => MchEpiFpReportService::zones(auth()->user()),
        ]);
    }

    /**
     * Download the merged Maternal / EPI / Family Planning report as PDF.
     */
    public function downloadMchEpiFpPdf(Request $request, PdfService $pdfService)
    {
        $filters = MchEpiFpReportService::normalizeFilters($request->only([
            'from', 'to', 'zone', 'program', 'search',
        ]));

        $report = MchEpiFpReportService::query($filters, auth()->user(), false);

        $pdf = $pdfService->generateMchEpiFpReport(
            $report,
            MchEpiFpReportService::zoneLabel($filters['zone']),
            MchEpiFpReportService::programLabel($filters['program'])
        );

        return $pdf->download("MCH_EPI_FP_Report_Sta_Ana_{$filters['from']}_{$filters['to']}.pdf");
    }

    /**
     * Redirect legacy standalone report URLs to the merged report,
     * translating the old month/year params into a from/to date range.
     */
    public function redirectLegacyMchEpiFp(Request $request)
    {
        return redirect()->route('reports.mch-epi-fp', self::legacyPeriodFilters($request));
    }

    public function redirectLegacyMchEpiFpDownload(Request $request)
    {
        return redirect()->route('reports.mch-epi-fp.download', self::legacyPeriodFilters($request));
    }

    /**
     * Map legacy month/year report filters onto the merged report's
     * from/to date range. Keeps an explicit zone filter when present.
     *
     * @return array<string, mixed>
     */
    private static function legacyPeriodFilters(Request $request): array
    {
        $params = $request->only(['month', 'year', 'zone']);

        if (isset($params['month'], $params['year']) && is_numeric($params['month']) && is_numeric($params['year'])) {
            $periodStart = Carbon::createFromDate((int) $params['year'], (int) $params['month'], 1);

            $params['from'] = $periodStart->startOfMonth()->toDateString();
            $params['to'] = $periodStart->endOfMonth()->toDateString();

            unset($params['month'], $params['year']);
        }

        return $params;
    }
}
