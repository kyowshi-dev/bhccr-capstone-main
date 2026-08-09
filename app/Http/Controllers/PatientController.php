<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientWithHouseholdRequest;
use App\Models\Household;
use App\Models\Patient;
use App\Models\Zone;
use App\Services\PatientQueryService;
use App\Services\PatientService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PatientController extends Controller
{
    // 1. List all patients
    public function index(Request $request)
    {
        $this->authorize('viewAny', Patient::class);

        $request->validate([
            'sort' => ['sometimes', Rule::in(['name', 'age', 'last_visit', 'created'])],
            'dir' => ['sometimes', Rule::in(['asc', 'desc'])],
        ]);

        $sort = $request->input('sort', 'created');
        $dir = $request->input('dir');
        if (! in_array($dir, ['asc', 'desc'], true)) {
            $dir = match ($sort) {
                'name' => 'asc',
                default => 'desc',
            };
        }

        $patients = PatientQueryService::paginateIndex($sort, $dir, auth()->user());

        return view('patients.index', [
            'patients' => $patients,
            'patientSort' => $sort,
            'patientDir' => $dir,
        ]);
    }

    // 2. Show the Registration Form
    public function create(Request $request)
    {
        $this->authorize('create', Patient::class);

        $selectedHouseholdId = $request->old('household_id') ?? $request->input('household_id');

        $transientHousehold = PatientService::ensureTransientHousehold();

        $user = auth()->user();

        $selectedHousehold = null;
        if (! empty($selectedHouseholdId) && $user->canAccessHousehold((int) $selectedHouseholdId)) {
            $selectedHousehold = Household::find($selectedHouseholdId);
        }

        $zonesQuery = Zone::query()
            ->orderBy('zone_number');
        if ($user->isZoneScoped()) {
            $zonesQuery->whereIn('id', $user->accessibleZoneIds());
        }

        return view('patients.create', [
            'selectedHouseholdId' => $selectedHouseholdId,
            'transientHouseholdId' => $transientHousehold->id,
            'transientHouseholdLabel' => $transientHousehold->family_name_head,
            'selectedHousehold' => $selectedHousehold,
            'zones' => $zonesQuery->get(),
        ]);
    }

    // 3. Save the New Patient with optional Household creation
    public function store(StorePatientWithHouseholdRequest $request)
    {
        $this->authorize('create', Patient::class);

        try {
            $patient = PatientService::register($request->validated());
        } catch (ValidationException $e) {
            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('patients.index')->with('success', 'Patient registered successfully!')->with('new_patient_id', $patient->id);
    }

    // 4. View Single Patient Profile
    public function show(int $id): View
    {
        $patient = Patient::with([
            'household',
            'activePregnancy' => fn (HasOne $query) => $query->withCount('visits'),
        ])
            ->withCount('immunizationRecords')
            ->findOrFail($id);

        $this->authorize('view', $patient);

        $history = $patient->consultations()
            ->with('worker')
            ->orderByDesc('created_at')
            ->get();

        return view('patients.show', compact('patient', 'history'));
    }
}
