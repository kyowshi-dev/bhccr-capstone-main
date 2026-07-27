<?php

namespace App\Http\Controllers;

use App\Helpers\HouseholdHelper;
use App\Services\PdfService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HouseholdController extends Controller
{
    public function index(Request $request): View
    {
        if (! auth()->user()->hasPermission('household')) {
            abort(403, 'Unauthorized');
        }

        $query = DB::table('households')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->select('households.*', 'zones.zone_number');

        // Apply search filter (family name or contact number)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('households.family_name_head', 'like', "%{$search}%")
                    ->orWhere('households.contact_number', 'like', "%{$search}%");
            });
        }

        // Apply zone filter
        if ($request->filled('zone_id')) {
            $query->where('households.zone_id', $request->input('zone_id'));
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->input('date_from'))->startOfDay();
            $query->where('households.created_at', '>=', $dateFrom);
        }

        if ($request->filled('date_to')) {
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->input('date_to'))->endOfDay();
            $query->where('households.created_at', '<=', $dateTo);
        }

        // Order and paginate
        $households = $query
            ->orderBy('zones.zone_number')
            ->orderBy('households.family_name_head')
            ->paginate(500)
            ->withQueryString();

        // Get all zones for filter dropdown
        $zones = DB::table('zones')
            ->select('id', 'zone_number')
            ->orderBy('zone_number')
            ->get();

        // Calculate stats
        $totalHouseholds = $households->total();
        $totalPopulation = HouseholdHelper::getTotalPopulation($households);
        $vulnerableGroups = HouseholdHelper::getVulnerableGroupsCount($households);
        $memberCounts = HouseholdHelper::enrichHouseholdsWithMemberCounts($households);

        // Get all households for global stats (across all pages/filters)
        $allHouseholds = DB::table('households')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->select('households.*', 'zones.zone_number');

        // Apply same filters to get stats for filtered dataset
        if ($request->filled('search')) {
            $search = $request->input('search');
            $allHouseholds->where(function ($q) use ($search) {
                $q->where('households.family_name_head', 'like', "%{$search}%")
                    ->orWhere('households.contact_number', 'like', "%{$search}%");
            });
        }
        if ($request->filled('zone_id')) {
            $allHouseholds->where('households.zone_id', $request->input('zone_id'));
        }
        if ($request->filled('date_from')) {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $request->input('date_from'))->startOfDay();
            $allHouseholds->where('households.created_at', '>=', $dateFrom);
        }
        if ($request->filled('date_to')) {
            $dateTo = Carbon::createFromFormat('Y-m-d', $request->input('date_to'))->endOfDay();
            $allHouseholds->where('households.created_at', '<=', $dateTo);
        }

        $allHouseholdsData = $allHouseholds->get();
        $statsVulnerable = HouseholdHelper::getVulnerableGroupsCount($allHouseholdsData);
        $statsTotalPopulation = HouseholdHelper::getTotalPopulation($allHouseholdsData);

        return view('households.index', [
            'households' => $households,
            'zones' => $zones,
            'search' => $request->input('search', ''),
            'zone_id' => $request->input('zone_id', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
            'totalHouseholds' => $totalHouseholds,
            'totalPopulation' => $statsTotalPopulation,
            'vulnerableGroups' => $statsVulnerable,
            'memberCounts' => $memberCounts,
        ]);
    }

    public function create(): View
    {
        if (! auth()->user()->hasPermission('household')) {
            abort(403, 'Unauthorized');
        }

        $zones = DB::table('zones')
            ->select('id', 'zone_number')
            ->orderBy('zone_number')
            ->get();

        return view('households.create', [
            'zones' => $zones,
        ]);
    }

    public function store(Request $request)
    {
        if (! auth()->user()->hasPermission('household')) {
            abort(403, 'Unauthorized');
        }

        $data = $request->validate([
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'family_name_head' => ['required', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\\-\\s()]*$/'],
        ]);

        DB::table('households')->insert([
            'zone_id' => $data['zone_id'],
            'family_name_head' => trim($data['family_name_head']),
            'contact_number' => $data['contact_number'] !== null ? trim($data['contact_number']) : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('households.index')
            ->with('success', 'Household registered successfully.');
    }

    public function edit($id): View
    {
        if (! auth()->user()->hasPermission('household')) {
            abort(403, 'Unauthorized');
        }

        $household = DB::table('households')
            ->where('id', $id)
            ->first();

        if (! $household) {
            abort(404, 'Household not found');
        }

        $zones = DB::table('zones')
            ->select('id', 'zone_number')
            ->orderBy('zone_number')
            ->get();

        return view('households.edit', [
            'household' => $household,
            'zones' => $zones,
        ]);
    }

    public function update(Request $request, $id)
    {
        if (! auth()->user()->hasPermission('household')) {
            abort(403, 'Unauthorized');
        }

        $household = DB::table('households')
            ->where('id', $id)
            ->first();

        if (! $household) {
            abort(404, 'Household not found');
        }

        $data = $request->validate([
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'family_name_head' => ['required', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\\-\\s()]*$/'],
        ]);

        DB::table('households')
            ->where('id', $id)
            ->update([
                'zone_id' => $data['zone_id'],
                'family_name_head' => trim($data['family_name_head']),
                'contact_number' => $data['contact_number'] !== null ? trim($data['contact_number']) : null,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('households.index')
            ->with('success', 'Household updated successfully.');
    }

    /**
     * Export selected households to CSV
     */
    public function exportCSV(Request $request): StreamedResponse
    {
        if (! auth()->user()->hasPermission('household')) {
            abort(403, 'Unauthorized');
        }

        $ids = $request->input('household_ids');
        if (! is_array($ids) || empty($ids)) {
            return redirect()
                ->route('households.index')
                ->with('error', 'Please select at least one household to export.');
        }

        // Sanitize IDs
        $ids = array_map('intval', $ids);

        $households = DB::table('households')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->select('households.*', 'zones.zone_number')
            ->whereIn('households.id', $ids)
            ->get();

        $memberCounts = HouseholdHelper::enrichHouseholdsWithMemberCounts($households);

        $fileName = 'households_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($households, $memberCounts) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Zone', 'Family Name', 'Contact Number', 'Registered Date', 'Member Count']);

            foreach ($households as $household) {
                fputcsv($file, [
                    $household->id,
                    $household->zone_number,
                    $household->family_name_head,
                    $household->contact_number ?? '',
                    $household->created_at,
                    $memberCounts[$household->id] ?? 0,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export selected households to PDF
     */
    public function exportPDF(Request $request): Response
    {
        if (! auth()->user()->hasPermission('household')) {
            abort(403, 'Unauthorized');
        }

        $ids = $request->input('household_ids');
        if (! is_array($ids) || empty($ids)) {
            return redirect()
                ->route('households.index')
                ->with('error', 'Please select at least one household to export.');
        }

        // Sanitize IDs
        $ids = array_map('intval', $ids);

        $households = DB::table('households')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->select('households.*', 'zones.zone_number')
            ->whereIn('households.id', $ids)
            ->orderBy('zones.zone_number')
            ->orderBy('households.family_name_head')
            ->get();

        $memberCounts = HouseholdHelper::enrichHouseholdsWithMemberCounts($households);
        $vulnerableGroups = HouseholdHelper::getVulnerableGroupsCount($households);
        $totalPopulation = HouseholdHelper::getTotalPopulation($households);

        // Use DomPDF directly for HTML generation
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($this->buildPdfHtml($households, $memberCounts, $vulnerableGroups, $totalPopulation));
        
        return $pdf->download('household_census_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Build HTML for PDF export
     */
    private function buildPdfHtml($households, $memberCounts, $vulnerableGroups, $totalPopulation): string
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
        $html .= '<p>Generated: ' . now()->format('F d, Y H:i:s') . '</p>';

        // Stats section
        $html .= '<div class="stats">';
        $html .= '<h3>Summary Statistics</h3>';
        $html .= '<p><strong>Total Households:</strong> ' . count($households) . '</p>';
        $html .= '<p><strong>Total Population:</strong> ' . $totalPopulation . '</p>';
        $html .= '<p><strong>Infants (0-1 year):</strong> ' . $vulnerableGroups['infants'] . '</p>';
        $html .= '<p><strong>Seniors (65+ years):</strong> ' . $vulnerableGroups['seniors'] . '</p>';
        $html .= '</div>';

        // Table
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
            $html .= '<td>' . htmlspecialchars((string)$household->zone_number) . '</td>';
            $html .= '<td>' . htmlspecialchars($household->family_name_head) . '</td>';
            $html .= '<td>' . htmlspecialchars($household->contact_number ?? '—') . '</td>';
            $html .= '<td style="text-align: center;">' . ($memberCounts[$household->id] ?? 0) . '</td>';
            $html .= '<td>' . Carbon::parse($household->created_at)->format('M d, Y') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<div class="footer">This is a system-generated report. For more information, contact your health center.</div>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Bulk update household zone
     */
    public function updateZone(Request $request)
    {
        if (! auth()->user()->hasPermission('household')) {
            abort(403, 'Unauthorized');
        }

        $data = $request->validate([
            'household_ids' => ['required', 'array', 'min:1'],
            'household_ids.*' => ['integer', 'exists:households,id'],
            'new_zone_id' => ['required', 'integer', 'exists:zones,id'],
        ]);

        $ids = $data['household_ids'];
        $newZoneId = $data['new_zone_id'];

        DB::table('households')
            ->whereIn('id', $ids)
            ->update([
                'zone_id' => $newZoneId,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('households.index')
            ->with('success', count($ids) . ' household(s) reassigned to the new zone.');
    }
}
