<?php

namespace App\Http\Controllers;

use App\Helpers\HouseholdHelper;
use App\Http\Requests\StoreHouseholdRequest;
use App\Http\Requests\UpdateHouseholdRequest;
use App\Models\Household;
use App\Services\HouseholdExportService;
use App\Services\HouseholdPdfService;
use App\Services\HouseholdQueryService;
use App\Services\HouseholdService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HouseholdController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizePermission('household');

        $user = auth()->user();

        $filters = [
            'search' => $request->input('search', ''),
            'zone_id' => $request->input('zone_id', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
        ];

        $households = HouseholdQueryService::paginateIndex($filters, $user, pageSize(500));
        $allHouseholdsData = HouseholdQueryService::allFiltered($filters, $user);

        return view('households.index', [
            'households' => $households,
            'zones' => HouseholdQueryService::zones($user),
            'search' => $filters['search'],
            'zone_id' => $filters['zone_id'],
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'totalHouseholds' => $households->total(),
            'totalPopulation' => HouseholdHelper::getTotalPopulation($allHouseholdsData),
            'vulnerableGroups' => HouseholdHelper::getVulnerableGroupsCount($allHouseholdsData),
            'memberCounts' => HouseholdHelper::enrichHouseholdsWithMemberCounts($households),
        ]);
    }

    public function create(): View
    {
        $this->authorizePermission('household');

        return view('households.create', [
            'zones' => HouseholdQueryService::zones(auth()->user()),
        ]);
    }

    public function store(StoreHouseholdRequest $request): RedirectResponse
    {
        $this->authorizePermission('household');

        $data = $request->validated();

        $this->guardZoneAccess((int) $data['zone_id']);

        HouseholdService::create($data);

        return redirect()
            ->route('households.index')
            ->with('success', 'Household registered successfully.');
    }

    public function edit($id): View
    {
        $this->authorizePermission('household');

        $household = HouseholdService::findOrFail($id);

        if (! auth()->user()->canAccessHousehold((int) $household->id)) {
            abort(403, 'This household is outside your assigned zones.');
        }

        return view('households.edit', [
            'household' => $household,
            'zones' => HouseholdQueryService::zones(auth()->user()),
        ]);
    }

    public function update(UpdateHouseholdRequest $request, $id): RedirectResponse
    {
        $this->authorizePermission('household');

        $household = HouseholdService::findOrFail($id);

        if (! auth()->user()->canAccessHousehold((int) $household->id)) {
            abort(403, 'This household is outside your assigned zones.');
        }

        $data = $request->validated();

        $this->guardZoneAccess((int) $data['zone_id']);

        HouseholdService::update($id, $data);

        return redirect()
            ->route('households.index')
            ->with('success', 'Household updated successfully.');
    }

    /**
     * Export selected households to CSV
     */
    public function exportCSV(Request $request): StreamedResponse|RedirectResponse
    {
        $this->authorizePermission('household');

        $ids = $request->input('household_ids');
        if (! is_array($ids) || empty($ids)) {
            return redirect()
                ->route('households.index')
                ->with('error', 'Please select at least one household to export.');
        }

        // Sanitize IDs
        $ids = array_map('intval', $ids);

        $households = HouseholdQueryService::byIds($ids, false, auth()->user());
        $memberCounts = HouseholdHelper::enrichHouseholdsWithMemberCounts($households);

        $fileName = 'households_'.date('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($households, $memberCounts) {
            $file = fopen('php://output', 'w');
            HouseholdExportService::writeCsvRows($file, $households, $memberCounts);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export selected households to PDF
     */
    public function exportPDF(Request $request): Response|RedirectResponse
    {
        $this->authorizePermission('household');

        $ids = $request->input('household_ids');
        if (! is_array($ids) || empty($ids)) {
            return redirect()
                ->route('households.index')
                ->with('error', 'Please select at least one household to export.');
        }

        // Sanitize IDs
        $ids = array_map('intval', $ids);

        $households = HouseholdQueryService::byIds($ids, true, auth()->user());
        $memberCounts = HouseholdHelper::enrichHouseholdsWithMemberCounts($households);
        $vulnerableGroups = HouseholdHelper::getVulnerableGroupsCount($households);
        $totalPopulation = HouseholdHelper::getTotalPopulation($households);

        return HouseholdPdfService::download(
            HouseholdPdfService::censusHtml($households, $memberCounts, $vulnerableGroups, $totalPopulation),
            'household_census_'.date('Y-m-d_His').'.pdf'
        );
    }

    /**
     * Zone scoping: zone-assigned workers may only write households in their
     * assigned zones.
     */
    private function guardZoneAccess(int $zoneId): void
    {
        $user = auth()->user();

        if ($user->isZoneScoped() && ! in_array($zoneId, $user->accessibleZoneIds(), true)) {
            abort(403, 'This zone is outside your assigned zones.');
        }
    }

    /**
     * Bulk update household zone
     */
    public function updateZone(Request $request): RedirectResponse
    {
        $this->authorizePermission('household');

        $data = $request->validate([
            'household_ids' => ['required', 'array', 'min:1'],
            'household_ids.*' => ['integer', 'exists:households,id'],
            'new_zone_id' => ['required', 'integer', 'exists:zones,id'],
        ]);

        $user = auth()->user();

        if ($user->isZoneScoped()) {
            $zoneIds = $user->accessibleZoneIds();
            $accessibleCount = Household::whereIn('id', $data['household_ids'])
                ->whereIn('zone_id', $zoneIds)
                ->count();

            if ($accessibleCount !== count($data['household_ids']) || ! in_array((int) $data['new_zone_id'], $zoneIds, true)) {
                abort(403, 'Households or target zone are outside your assigned zones.');
            }
        }

        HouseholdService::reassignZones($data['household_ids'], $data['new_zone_id']);

        return redirect()
            ->route('households.index')
            ->with('success', count($data['household_ids']).' household(s) reassigned to the new zone.');
    }
}
