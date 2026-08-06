<?php

namespace App\Http\Controllers;

use App\Models\Zone;
use App\Services\ZoneQueryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    public function index(): View
    {
        $this->authorizePermission('zones');

        return view('zones.index', [
            'zones' => ZoneQueryService::paginated(),
        ]);
    }

    public function create(): View
    {
        $this->authorizePermission('zones');

        return view('zones.create', [
            'healthWorkers' => ZoneQueryService::healthWorkers(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizePermission('zones');

        $validated = $request->validate([
            'zone_number' => ['required', 'string', 'max:255', 'unique:zones,zone_number'],
            'assigned_worker_id' => ['nullable', 'exists:health_workers,id'],
        ]);

        Zone::create([
            'zone_number' => $validated['zone_number'],
            'assigned_worker_id' => $validated['assigned_worker_id'] ?? null,
        ]);

        return redirect()
            ->route('zones.index')
            ->with('success', 'Zone added successfully.');
    }

    public function show($id): View
    {
        $this->authorizePermission('zones');

        $zone = Zone::with('assignedWorker')->findOrFail($id);

        return view('zones.show', [
            'zone' => $zone,
        ]);
    }

    public function edit($id): View
    {
        $this->authorizePermission('zones');

        return view('zones.edit', [
            'zone' => Zone::findOrFail($id),
            'healthWorkers' => ZoneQueryService::healthWorkers(),
        ]);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $this->authorizePermission('zones');

        $zone = Zone::findOrFail($id);

        $validated = $request->validate([
            'zone_number' => ['required', 'string', 'max:255', 'unique:zones,zone_number,'.$id],
            'assigned_worker_id' => ['nullable', 'exists:health_workers,id'],
        ]);

        $zone->update([
            'zone_number' => $validated['zone_number'],
            'assigned_worker_id' => $validated['assigned_worker_id'] ?? null,
        ]);

        return redirect()
            ->route('zones.show', $id)
            ->with('success', 'Zone updated successfully.');
    }

    public function destroy($id): RedirectResponse
    {
        $this->authorizePermission('zones');

        $zone = Zone::findOrFail($id);

        // Check if zone has households
        if ($zone->households()->count() > 0) {
            return redirect()
                ->route('zones.index')
                ->with('error', 'Cannot delete zone that has households. Please reassign or delete households first.');
        }

        $zone->delete();

        return redirect()
            ->route('zones.index')
            ->with('success', 'Zone deleted successfully.');
    }
}
