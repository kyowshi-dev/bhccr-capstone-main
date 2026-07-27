<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePatientWithHouseholdRequest;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

        $query = DB::table('patients')
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->leftJoinSub(
                DB::table('consultations')
                    ->select('patient_id', DB::raw('MAX(created_at) as last_visit'))
                    ->groupBy('patient_id'),
                'latest_consultations',
                function ($join) {
                    $join->on('patients.id', '=', 'latest_consultations.patient_id');
                }
            )
            ->select(
                'patients.*',
                'households.family_name_head',
                'households.zone_id',
                'households.contact_number',
                'latest_consultations.last_visit'
            );

        match ($sort) {
            'name' => $query
                ->orderBy('patients.last_name', $dir)
                ->orderBy('patients.first_name', $dir),
            'age' => $dir === 'asc'
                ? $query->orderByDesc('patients.date_of_birth')
                : $query->orderBy('patients.date_of_birth', 'asc'),
            'last_visit' => $query
                ->orderByRaw('latest_consultations.last_visit IS NULL ASC')
                ->orderBy('latest_consultations.last_visit', $dir),
            default => $query->orderBy('patients.created_at', $dir),
        };

        $patients = $query->paginate(20)->withQueryString();

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

        $transientHousehold = DB::table('households')
            ->where(function ($qb) {
                $qb->whereRaw('LOWER(family_name_head) LIKE ?', ['%transient%'])
                    ->orWhereRaw('LOWER(family_name_head) LIKE ?', ['%unmapped%']);
            })
            ->select(['id', 'family_name_head'])
            ->first();

        // Ensure transient household exists
        if (! $transientHousehold) {
            $transientId = DB::table('households')->insertGetId([
                'zone_id' => 1,
                'family_name_head' => 'Transient/Unmapped',
                'contact_number' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $transientHousehold = (object) ['id' => $transientId, 'family_name_head' => 'Transient/Unmapped'];
        }

        $selectedHousehold = null;
        if (! empty($selectedHouseholdId)) {
            $selectedHousehold = DB::table('households')
                ->join('zones', 'households.zone_id', '=', 'zones.id')
                ->select('households.id', 'households.family_name_head', 'zones.zone_number', 'households.contact_number')
                ->where('households.id', $selectedHouseholdId)
                ->first();
        }

        // Fetch zones for new household creation
        $zones = DB::table('zones')
            ->select('id', 'zone_number')
            ->orderBy('zone_number')
            ->get();

        return view('patients.create', [
            'selectedHouseholdId' => $selectedHouseholdId,
            'transientHouseholdId' => $transientHousehold?->id,
            'transientHouseholdLabel' => $transientHousehold?->family_name_head,
            'selectedHousehold' => $selectedHousehold,
            'zones' => $zones,
        ]);
    }

    // 3. Save the New Patient with optional Household creation
    public function store(StorePatientWithHouseholdRequest $request)
    {
        $this->authorize('create', Patient::class);

        $validated = $request->validated();

        // --- 1. HANDLE HOUSEHOLD CREATION OR SELECTION ---
        $householdId = $validated['household_id'];

        if ((int) $validated['create_new_household'] === 1) {
            // Create the household atomically with the patient
            $householdId = DB::table('households')->insertGetId([
                'zone_id' => $validated['new_household_zone_id'],
                'family_name_head' => trim($validated['new_household_family_name_head']),
                'contact_number' => $validated['new_household_contact_number'] !== null ? trim($validated['new_household_contact_number']) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $zoneNumber = DB::table('households')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->where('households.id', $householdId)
            ->value('zones.zone_number');

        $residentialAddress = trim($zoneNumber).' Sta. Ana, Tagoloan';

        // --- 2. DUPLICATE CHECK ---
        // Prevents double-entry of the same person
        $exists = DB::table('patients')
            ->where('first_name', $validated['first_name'])
            ->where('last_name', $validated['last_name'])
            ->where('date_of_birth', $validated['date_of_birth'])
            ->exists();

        if ($exists) {
            // In case of duplicate, rollback household creation if we just created it
            if ((int) $validated['create_new_household'] === 1) {
                DB::table('households')->where('id', $householdId)->delete();
            }

            return back()->withInput()->withErrors(['first_name' => 'This patient is already registered in the system!']);
        }

        // --- 3. INSERT PATIENT DATA (Sanitized) ---
        $patientId = DB::table('patients')->insertGetId([
            'household_id' => $householdId,
            // Auto-Capitalize Names
            'first_name' => ucwords(strtolower($validated['first_name'])),
            'last_name' => ucwords(strtolower($validated['last_name'])),
            'middle_name' => $validated['middle_name'] ? ucfirst(strtolower($validated['middle_name'])) : null,

            'suffix' => $validated['suffix'],
            'sex' => $validated['sex'],
            'date_of_birth' => $validated['date_of_birth'],
            'birth_place' => $validated['birth_place'],
            'blood_type' => $validated['blood_type'],
            'civil_status' => $validated['civil_status'],
            'educational_attainment' => $validated['educational_attainment'],
            'employment_status' => $validated['employment_status'],

            'mother_name' => $validated['mother_name'],
            'spouse_name' => $validated['spouse_name'],
            'family_relationship' => $validated['family_relationship'],
            'residential_address' => $residentialAddress,
            'is_philhealth_member' => $validated['is_philhealth_member'],
            'status_type' => $validated['status_type'] ?? null,
            'philhealth_no' => $validated['philhealth_no'] ?? null,
            'membership_category' => $validated['membership_category'] ?? null,
            'is_pcb_member' => $validated['is_pcb_member'],

            'has_4ps' => $validated['has_4ps'],
            'has_nhts' => $validated['has_nhts'],

            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('patients.index')->with('success', 'Patient registered successfully!')->with('new_patient_id', $patientId);
    }

    // 4. View Single Patient Profile
    public function show($id)
    {
        $patient = Patient::findOrFail($id);

        $this->authorize('view', $patient);

        $patient = DB::table('patients')
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->where('patients.id', $id)
            ->select('patients.*', 'households.family_name_head', 'households.zone_id')
            ->first();

        // 2. Calculate Age
        $patient->age = Carbon::parse($patient->date_of_birth)->age;

        // 3. Load Consultations (History) – worker_id is health_workers.id
        $history = DB::table('consultations')
            ->leftJoin('health_workers', 'consultations.worker_id', '=', 'health_workers.id')
            ->where('patient_id', $id)
            ->select(
                'consultations.*',
                DB::raw($this->dbConcat(['health_workers.first_name', 'health_workers.last_name']).' as worker_name'),
                'consultations.nature_of_visit as complaint_name'
            )
            ->orderByDesc('consultations.created_at')
            ->get();

        $immunizationCount = DB::table('immunization_records')->where('patient_id', $id)->count();

        return view('patients.show', compact('patient', 'history', 'immunizationCount'));
    }
}
