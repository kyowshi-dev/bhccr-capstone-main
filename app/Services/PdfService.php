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
}
