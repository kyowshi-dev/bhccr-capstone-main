<?php

namespace App\Http\Controllers;

use App\Models\HealthWorker;
use App\Models\Zone;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    public function index()
    {
        if (! auth()->user()->hasPermission('zones')) {
            abort(403, 'Unauthorized');
        }

        $zones = Zone::with('assignedWorker')
            ->orderBy('zone_number')
            ->paginate(10)
            ->withQueryString();

        return view('zones.index', [
            'zones' => $zones,
        ]);
    }

    public function create()
    {
        if (! auth()->user()->hasPermission('zones')) {
            abort(403, 'Unauthorized');
        }

        $healthWorkers = HealthWorker::query()
            ->orderBy('first_name')
            ->get();

        return view('zones.create', [
            'healthWorkers' => $healthWorkers,
        ]);
    }

    public function store(Request $request)
    {
        if (! auth()->user()->hasPermission('zones')) {
            abort(403, 'Unauthorized');
        }

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

    public function show($id)
    {
        if (! auth()->user()->hasPermission('zones')) {
            abort(403, 'Unauthorized');
        }

        $zone = Zone::with('assignedWorker')->findOrFail($id);

        return view('zones.show', [
            'zone' => $zone,
        ]);
    }

    public function edit($id)
    {
        if (! auth()->user()->hasPermission('zones')) {
            abort(403, 'Unauthorized');
        }

        $zone = Zone::findOrFail($id);

        $healthWorkers = HealthWorker::query()
            ->orderBy('first_name')
            ->get();

        return view('zones.edit', [
            'zone' => $zone,
            'healthWorkers' => $healthWorkers,
        ]);
    }

    public function update(Request $request, $id)
    {
        if (! auth()->user()->hasPermission('zones')) {
            abort(403, 'Unauthorized');
        }

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

    public function destroy($id)
    {
        // Check authorization
        if (! auth()->user()->hasPermission('zones')) {
            abort(403, 'Unauthorized');
        }

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
