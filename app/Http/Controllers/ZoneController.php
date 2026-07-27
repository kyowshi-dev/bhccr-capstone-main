<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ZoneController extends Controller
{
    public function index()
    {
        if (! auth()->user()->hasPermission('zones')) {
            abort(403, 'Unauthorized');
        }

        $zones = DB::table('zones')
            ->leftJoin('health_workers', 'zones.assigned_worker_id', '=', 'health_workers.id')
            ->select(
                'zones.id',
                'zones.zone_number',
                'zones.assigned_worker_id',
                'zones.created_at',
                'zones.updated_at',
                DB::raw("CONCAT(health_workers.first_name, ' ', health_workers.last_name) as worker_name")
            )
            ->orderBy('zones.zone_number')
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

        $healthWorkers = DB::table('health_workers')
            ->select('id', DB::raw("CONCAT(first_name, ' ', last_name) as name"))
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

        DB::table('zones')->insert([
            'zone_number' => $validated['zone_number'],
            'assigned_worker_id' => $validated['assigned_worker_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
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

        $zone = DB::table('zones')
            ->leftJoin('health_workers', 'zones.assigned_worker_id', '=', 'health_workers.id')
            ->select(
                'zones.id',
                'zones.zone_number',
                'zones.assigned_worker_id',
                'zones.created_at',
                'zones.updated_at',
                DB::raw("CONCAT(health_workers.first_name, ' ', health_workers.last_name) as worker_name"),
                'health_workers.role as worker_role'
            )
            ->where('zones.id', $id)
            ->first();

        if (! $zone) {
            abort(404, 'Zone not found');
        }

        // Get household count
        $householdCount = DB::table('households')->where('zone_id', $id)->count();

        // Get patient count
        $patientCount = DB::table('households')
            ->where('zone_id', $id)
            ->join('patients', 'households.id', '=', 'patients.household_id')
            ->count();

        $zone->household_count = $householdCount;
        $zone->patient_count = $patientCount;

        return view('zones.show', [
            'zone' => $zone,
        ]);
    }

    public function edit($id)
    {
        if (! auth()->user()->hasPermission('zones')) {
            abort(403, 'Unauthorized');
        }

        $zone = DB::table('zones')->where('id', $id)->first();

        if (! $zone) {
            abort(404, 'Zone not found');
        }

        $healthWorkers = DB::table('health_workers')
            ->select('id', DB::raw("CONCAT(first_name, ' ', last_name) as name"))
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

        $zone = DB::table('zones')->where('id', $id)->first();

        if (! $zone) {
            abort(404, 'Zone not found');
        }

        $validated = $request->validate([
            'zone_number' => ['required', 'string', 'max:255', 'unique:zones,zone_number,'.$id],
            'assigned_worker_id' => ['nullable', 'exists:health_workers,id'],
        ]);

        DB::table('zones')
            ->where('id', $id)
            ->update([
                'zone_number' => $validated['zone_number'],
                'assigned_worker_id' => $validated['assigned_worker_id'] ?? null,
                'updated_at' => now(),
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

        $zone = DB::table('zones')->where('id', $id)->first();

        if (! $zone) {
            abort(404, 'Zone not found');
        }

        // Check if zone has households
        $householdCount = DB::table('households')->where('zone_id', $id)->count();

        if ($householdCount > 0) {
            return redirect()
                ->route('zones.index')
                ->with('error', 'Cannot delete zone that has households. Please reassign or delete households first.');
        }

        DB::table('zones')->where('id', $id)->delete();

        return redirect()
            ->route('zones.index')
            ->with('success', 'Zone deleted successfully.');
    }
}
