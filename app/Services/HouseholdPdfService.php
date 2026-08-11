<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;

final class HouseholdPdfService
{
    public static function download(string $html, string $fileName): Response
    {
        return Pdf::loadHTML($html)->download($fileName);
    }

    public static function censusHtml($households, $memberCounts, $vulnerableGroups, $totalPopulation): string
    {
        $html = '<html><head>';
        $html .= '<meta charset="UTF-8">';
        $html .= '<style>';
        $html .= 'body { font-family: Arial, sans-serif; margin: 20px; }';
        $html .= 'h1 { color: #003366; font-size: 24px; margin-bottom: 10px; }';
        $html .= 'h3 { color: #333; font-size: 16px; margin-top: 20px; margin-bottom: 10px; }';
        $html .= 'p { margin: 5px 0; font-size: 12px; }';
        $html .= '.stats { background-color: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px; }';
        $html .= '.stats p { margin: 8px 0; }';
        $html .= 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
        $html .= 'thead { background-color: #003366; color: white; }';
        $html .= 'th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 11px; }';
        $html .= 'tbody tr:nth-child(even) { background-color: #f9f9f9; }';
        $html .= '.footer { margin-top: 30px; font-size: 10px; color: #666; text-align: center; border-top: 1px solid #ddd; padding-top: 10px; }';
        $html .= '</style></head><body>';

        $html .= '<h1>Household Census Report</h1>';
        $html .= '<p>Generated: '.now()->format('F d, Y H:i:s').'</p>';

        $html .= '<div class="stats">';
        $html .= '<h3>Summary Statistics</h3>';
        $html .= '<p><strong>Total Households:</strong> '.count($households).'</p>';
        $html .= '<p><strong>Total Population:</strong> '.$totalPopulation.'</p>';
        $html .= '<p><strong>Infants (0-1 year):</strong> '.$vulnerableGroups['infants'].'</p>';
        $html .= '<p><strong>Seniors (65+ years):</strong> '.$vulnerableGroups['seniors'].'</p>';
        $html .= '</div>';

        $html .= '<table>';
        $html .= '<thead><tr>';
        $html .= '<th>Zone</th>';
        $html .= '<th>Family Name</th>';
        $html .= '<th>Contact</th>';
        $html .= '<th style="text-align: center;">Members</th>';
        $html .= '<th>Registered</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';

        foreach ($households as $household) {
            $html .= '<tr>';
            $html .= '<td>'.htmlspecialchars((string) $household->zone_number).'</td>';
            $html .= '<td>'.htmlspecialchars($household->family_name_head).'</td>';
            $html .= '<td>'.htmlspecialchars($household->contact_number ?? '-').'</td>';
            $html .= '<td style="text-align: center;">'.($memberCounts[$household->id] ?? 0).'</td>';
            $html .= '<td>'.Carbon::parse($household->created_at)->format('M d, Y').'</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<div class="footer">This is a system-generated report. For more information, contact your health center.</div>';
        $html .= '</body></html>';

        return $html;
    }
}
