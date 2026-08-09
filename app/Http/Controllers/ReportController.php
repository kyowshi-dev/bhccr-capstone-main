<?php

namespace App\Http\Controllers;

use App\Services\FamilyPlanningReportService;
use App\Services\ImmunizationReportService;
use App\Services\MaternalCareReportService;
use App\Services\MorbidityReportService;
use App\Services\NcdReportService;
use App\Services\PdfService;
use App\Services\ReferralReportService;
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
     * FHSIS-style Maternal Care Report.
     */
    public function maternalCare(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $zone = $request->input('zone', null);

        $report = MaternalCareReportService::query($month, $year, $zone, auth()->user());

        return view('reports.maternal_care', [
            'report' => $report,
            'month' => (int) $month,
            'year' => (int) $year,
            'zones' => MaternalCareReportService::zones(auth()->user()),
            'selectedZone' => $zone,
        ]);
    }

    /**
     * Download FHSIS Maternal Care Report as PDF.
     */
    public function downloadMaternalCarePdf(Request $request, PdfService $pdfService)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $zone = $request->input('zone', null);

        $report = MaternalCareReportService::query($month, $year, $zone, auth()->user());

        $pdf = $pdfService->generateMaternalCareReport($report, MaternalCareReportService::zoneLabel($zone));

        return $pdf->download("Maternal_Care_Report_Sta_Ana_{$month}_{$year}.pdf");
    }

    /**
     * FHSIS-style EPI Immunization Report.
     */
    public function immunization(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $zone = $request->input('zone', null);

        $report = ImmunizationReportService::query($month, $year, $zone, auth()->user());

        return view('reports.immunization', [
            'report' => $report,
            'month' => (int) $month,
            'year' => (int) $year,
            'zones' => ImmunizationReportService::zones(auth()->user()),
            'selectedZone' => $zone,
        ]);
    }

    /**
     * Download FHSIS EPI Immunization Report as PDF.
     */
    public function downloadImmunizationPdf(Request $request, PdfService $pdfService)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $zone = $request->input('zone', null);

        $report = ImmunizationReportService::query($month, $year, $zone, auth()->user());

        $pdf = $pdfService->generateImmunizationReport($report, ImmunizationReportService::zoneLabel($zone));

        return $pdf->download("Immunization_Report_Sta_Ana_{$month}_{$year}.pdf");
    }

    /**
     * FHSIS Family Planning Report.
     */
    public function familyPlanning(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $zone = $request->input('zone', null);

        $report = FamilyPlanningReportService::query($month, $year, $zone, auth()->user());

        return view('reports.family_planning', [
            'report' => $report,
            'month' => (int) $month,
            'year' => (int) $year,
            'zones' => FamilyPlanningReportService::zones(auth()->user()),
            'selectedZone' => $zone,
        ]);
    }

    /**
     * Download FHSIS Family Planning Report as PDF.
     */
    public function downloadFamilyPlanningPdf(Request $request, PdfService $pdfService)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $zone = $request->input('zone', null);

        $report = FamilyPlanningReportService::query($month, $year, $zone, auth()->user());

        $pdf = $pdfService->generateFamilyPlanningReport($report, FamilyPlanningReportService::zoneLabel($zone));

        return $pdf->download("Family_Planning_Report_Sta_Ana_{$month}_{$year}.pdf");
    }

    /**
     * FHSIS Adult Care / NCD Report.
     */
    public function ncd(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $zone = $request->input('zone', null);

        $report = NcdReportService::query($month, $year, $zone, auth()->user());

        return view('reports.ncd', [
            'report' => $report,
            'month' => (int) $month,
            'year' => (int) $year,
            'zones' => NcdReportService::zones(auth()->user()),
            'selectedZone' => $zone,
        ]);
    }

    /**
     * Download FHSIS Adult Care / NCD Report as PDF.
     */
    public function downloadNcdPdf(Request $request, PdfService $pdfService)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $zone = $request->input('zone', null);

        $report = NcdReportService::query($month, $year, $zone, auth()->user());

        $pdf = $pdfService->generateNcdReport($report, NcdReportService::zoneLabel($zone));

        return $pdf->download("NCD_Report_Sta_Ana_{$month}_{$year}.pdf");
    }

    /**
     * FHSIS Referral Report.
     */
    public function referrals(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $zone = $request->input('zone', null);

        $report = ReferralReportService::query($month, $year, $zone, auth()->user());

        return view('reports.referrals', [
            'report' => $report,
            'month' => (int) $month,
            'year' => (int) $year,
            'zones' => ReferralReportService::zones(auth()->user()),
            'selectedZone' => $zone,
        ]);
    }

    /**
     * Download FHSIS Referral Report as PDF.
     */
    public function downloadReferralsPdf(Request $request, PdfService $pdfService)
    {
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        $zone = $request->input('zone', null);

        $report = ReferralReportService::query($month, $year, $zone, auth()->user());

        $pdf = $pdfService->generateReferralReport($report, ReferralReportService::zoneLabel($zone));

        return $pdf->download("Referral_Report_Sta_Ana_{$month}_{$year}.pdf");
    }
}
