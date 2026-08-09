<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class PdfService
{
    /**
     * Generate PDF for FHSIS Morbidity Report
     */
    public function generateMorbidityReport(Collection $rows, int $totalCases, string $reportDate, int $month, int $year, string $sexLabel, string $zoneLabel, string $ageGroupLabel): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdfs.morbidity_report', [
            'rows' => $rows,
            'totalCases' => $totalCases,
            'reportDate' => $reportDate,
            'month' => $month,
            'year' => $year,
            'sexLabel' => $sexLabel,
            'zoneLabel' => $zoneLabel,
            'ageGroupLabel' => $ageGroupLabel,
        ]);
    }

    /**
     * Generate PDF for the FHSIS Maternal Care Report.
     *
     * @param  array<string, mixed>  $report
     */
    public function generateMaternalCareReport(array $report, string $zoneLabel): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdfs.maternal_care_report', $report + ['zoneLabel' => $zoneLabel]);
    }

    /**
     * Generate PDF for the FHSIS EPI Immunization Report.
     *
     * @param  array<string, mixed>  $report
     */
    public function generateImmunizationReport(array $report, string $zoneLabel): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdfs.immunization_report', $report + ['zoneLabel' => $zoneLabel]);
    }

    /**
     * Generate PDF for the FHSIS Family Planning Report.
     *
     * @param  array<string, mixed>  $report
     */
    public function generateFamilyPlanningReport(array $report, string $zoneLabel): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdfs.family_planning_report', $report + ['zoneLabel' => $zoneLabel]);
    }

    /**
     * Generate PDF for the FHSIS Adult Care / NCD Report.
     *
     * @param  array<string, mixed>  $report
     */
    public function generateNcdReport(array $report, string $zoneLabel): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdfs.ncd_report', $report + ['zoneLabel' => $zoneLabel]);
    }

    /**
     * Generate PDF for the FHSIS Referral Report.
     *
     * @param  array<string, mixed>  $report
     */
    public function generateReferralReport(array $report, string $zoneLabel): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdfs.referral_report', $report + ['zoneLabel' => $zoneLabel]);
    }
}
