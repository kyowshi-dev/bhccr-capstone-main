<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdministerVaccineRequest;
use App\Http\Requests\MarkNoShowRequest;
use App\Http\Requests\StoreInfantWithHouseholdRequest;
use App\Models\Household;
use App\Models\Immunization;
use App\Models\Patient;
use App\Models\Vaccine;
use App\Models\Zone;
use App\Services\ChildImmunizationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImmunizationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeImmunizations();

        $mode = $this->resolveMode($request);
        $zoneId = $request->filled('zone_id') ? (int) $request->input('zone_id') : null;
        $date = $request->filled('date') ? $request->input('date') : Carbon::today()->toDateString();
        $today = Carbon::today()->toDateString();

        $service = $this->service();
        $queues = [];

        $recentRecords = DB::table('immunization_records')
            ->join('patients', 'immunization_records.patient_id', '=', 'patients.id')
            ->join('vaccines_lookup', 'immunization_records.vaccine_id', '=', 'vaccines_lookup.id')
            ->leftJoin('health_workers', 'immunization_records.administered_by', '=', 'health_workers.id')
            ->select(
                'immunization_records.id',
                'immunization_records.patient_id',
                'immunization_records.date_given',
                'immunization_records.dose_number',
                'immunization_records.next_due_date',
                'patients.first_name',
                'patients.last_name',
                'vaccines_lookup.vaccine_name',
                DB::raw($this->dbConcat(['health_workers.first_name', 'health_workers.last_name']).' as worker_name')
            )
            ->orderByDesc('immunization_records.date_given')
            ->limit(20)
            ->get();

        if ($mode === 'child') {
            foreach (['due', 'overdue', 'out_of_window', 'no_show'] as $key) {
                $queues[$key] = $service->queue($key, $zoneId, $key === 'due' ? $date : null);
            }

            $dueTodayCount = $queues['due']->pluck('patient.id')->unique()->count();
            $overdueCount = $queues['overdue']->pluck('patient.id')->unique()->count();
            $outOfWindowCount = $queues['out_of_window']->count();
            $noShowCount = $queues['no_show']->count();

            $dueTodayPatients = $queues['due']
                ->map(fn (array $entry) => (object) [
                    'patient_id' => $entry['patient']->id,
                    'first_name' => $entry['patient']->first_name,
                    'last_name' => $entry['patient']->last_name,
                    'next_due_date' => $entry['due_date']->toDateString(),
                    'dose_number' => $entry['dose_number'],
                    'vaccine_name' => $entry['vaccine']->vaccine_name,
                ])
                ->values();
        } else {
            $dueQueue = $this->legacyDueQueue($zoneId);

            $dueTodayPatients = (clone $dueQueue)
                ->where('ir.next_due_date', '=', $today)
                ->limit(50)
                ->get();
            $overdueCount = (clone $dueQueue)
                ->where('ir.next_due_date', '<', $today)
                ->distinct('patients.id')
                ->count('patients.id');
            $dueTodayCount = (clone $dueQueue)
                ->where('ir.next_due_date', '=', $today)
                ->distinct('patients.id')
                ->count('patients.id');
            $outOfWindowCount = 0;
            $noShowCount = 0;
        }

        $infantCutoff = Carbon::today()->subYear()->toDateString();
        $patientBase = DB::table('patients')->join('households', 'patients.household_id', '=', 'households.id');

        if ($zoneId !== null) {
            $patientBase->where('households.zone_id', $zoneId);
        }

        $infantTotal = (clone $patientBase)
            ->where('patients.date_of_birth', '>=', $infantCutoff)
            ->count();
        $infantWithAnyDose = (clone $patientBase)
            ->join('immunization_records', 'immunization_records.patient_id', '=', 'patients.id')
            ->where('patients.date_of_birth', '>=', $infantCutoff)
            ->distinct('immunization_records.patient_id')
            ->count('immunization_records.patient_id');
        $infantCoveragePercent = $infantTotal > 0
            ? (int) round(($infantWithAnyDose / $infantTotal) * 100)
            : null;

        $totalGiven = DB::table('immunization_records')->count();
        $patientsWithRecords = DB::table('immunization_records')->distinct('patient_id')->count('patient_id');

        $zones = Zone::orderBy('zone_number')->get();

        return view('immunizations.index', [
            'mode' => $mode,
            'zones' => $zones,
            'zoneId' => $zoneId,
            'date' => $date,
            'queues' => $queues,
            'recentRecords' => $recentRecords,
            'dueTodayPatients' => $dueTodayPatients,
            'dueTodayCount' => $dueTodayCount,
            'overdueCount' => $overdueCount,
            'outOfWindowCount' => $outOfWindowCount,
            'noShowCount' => $noShowCount,
            'infantCoveragePercent' => $infantCoveragePercent,
            'infantTotal' => $infantTotal,
            'totalGiven' => $totalGiven,
            'patientsWithRecords' => $patientsWithRecords,
        ]);
    }

    public function forPatient($id)
    {
        $this->authorizeImmunizations();

        $patient = Patient::with('household')->findOrFail($id);

        $isChild = $patient->age < 18;
        $allowedCategories = $isChild ? ['Child', 'Both'] : ['Adult', 'Both'];

        $records = DB::table('immunization_records')
            ->join('vaccines_lookup', 'immunization_records.vaccine_id', '=', 'vaccines_lookup.id')
            ->leftJoin('health_workers', 'immunization_records.administered_by', '=', 'health_workers.id')
            ->where('immunization_records.patient_id', $id)
            ->select(
                'immunization_records.*',
                'vaccines_lookup.vaccine_name',
                'vaccines_lookup.vaccine_code',
                DB::raw($this->dbConcat(['health_workers.first_name', 'health_workers.last_name']).' as administered_by_name')
            )
            ->orderByDesc('immunization_records.date_given')
            ->get();

        $vaccines = DB::table('vaccines_lookup')
            ->whereIn('category', $allowedCategories)
            ->orderBy('sort_order')
            ->get();
        $healthWorkers = DB::table('health_workers')
            ->orderBy('last_name')
            ->get();

        $recordsByVaccine = $records->groupBy('vaccine_id');
        $schedule = $vaccines->map(function ($vaccine) use ($recordsByVaccine) {
            $doses = $recordsByVaccine->get($vaccine->id, collect());
            $latestDose = $doses->sortByDesc('date_given')->first();

            return (object) [
                'vaccine' => $vaccine,
                'doses_given' => $doses->count(),
                'latest_date' => $latestDose?->date_given,
                'latest_dose_number' => $latestDose?->dose_number,
                'next_due_date' => $latestDose?->next_due_date,
            ];
        });

        $service = $this->service();
        $vaccineModels = Vaccine::whereIn('category', $allowedCategories)->with('schedules')->get();
        $statuses = $vaccineModels->mapWithKeys(fn (Vaccine $v) => [$v->id => $service->statusFor($patient, $v)]);
        $eligibility = $vaccineModels->mapWithKeys(fn (Vaccine $v) => [$v->id => $service->eligibility($patient, $v)]);
        $schedulesByVaccine = $vaccineModels->mapWithKeys(fn (Vaccine $v) => [$v->id => $v->schedules]);

        $currentWorkerId = DB::table('health_workers')->where('user_id', auth()->id())->value('id');

        return view('immunizations.patient', [
            'patient' => $patient,
            'records' => $records,
            'vaccines' => $vaccines,
            'schedule' => $schedule,
            'healthWorkers' => $healthWorkers,
            'currentWorkerId' => $currentWorkerId,
            'statuses' => $statuses,
            'eligibility' => $eligibility,
            'schedulesByVaccine' => $schedulesByVaccine,
        ]);
    }

    public function administer(AdministerVaccineRequest $request, $id)
    {
        $patient = Patient::findOrFail($id);
        $vaccine = Vaccine::findOrFail($request->input('vaccine_id'));

        if (! $this->vaccineMatchesPatientAge($patient, $vaccine)) {
            return back()->withErrors(['vaccine_id' => 'This vaccine is not appropriate for this patient.'])->withInput();
        }

        try {
            $record = $this->service()->administer($patient, $vaccine, [
                'temp_recorded' => $request->input('temp_recorded'),
                'notes' => $request->input('notes'),
                'override_reason' => $request->input('override_reason'),
                'administered_by' => $this->currentWorkerId(),
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $next = $record->next_due_date
            ? ' Next dose due '.$record->next_due_date->format('M j, Y').'.'
            : '';

        return redirect()
            ->route('immunizations.patient', $patient->id)
            ->with('success', $vaccine->vaccine_name.' recorded (dose '.$record->dose_number.').'.$next);
    }

    public function enrollInfant(StoreInfantWithHouseholdRequest $request)
    {
        $data = $request->validated();

        $householdId = $data['household_id'] ?? null;

        if ($householdId === null) {
            $household = Household::create([
                'zone_id' => $data['zone_id'],
                'family_name_head' => $data['family_name_head'],
                'contact_number' => $data['contact_number'] ?? null,
            ]);
            $householdId = $household->id;
        }

        $duplicate = Patient::where('household_id', $householdId)
            ->where('first_name', $data['first_name'])
            ->where('last_name', $data['last_name'])
            ->whereDate('date_of_birth', $data['date_of_birth'])
            ->exists();

        if ($duplicate) {
            return back()
                ->withErrors(['duplicate' => 'An infant with this name and birth date already exists in this household.'])
                ->withInput();
        }

        $guardian = $data['guardian_name'] ?? '';

        $patient = Patient::create([
            'household_id' => $householdId,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'suffix' => $data['suffix'] ?? null,
            'sex' => $data['sex'],
            'date_of_birth' => $data['date_of_birth'],
            'birth_weight' => $data['birth_weight'] ?? null,
            'guardian_name' => $data['guardian_name'] ?? null,
            'mother_name' => $guardian,
            'spouse_name' => '',
            'family_relationship' => 'Son',
            'residential_address' => '',
            'civil_status' => 'Single',
        ]);

        return redirect()
            ->route('immunizations.patient', $patient->id)
            ->with('success', 'Infant enrolled ('.$patient->first_name.' '.$patient->last_name.').');
    }

    public function toggleNoShow(MarkNoShowRequest $request, Immunization $record)
    {
        $mark = $request->boolean('no_show');

        if ($mark && ! $record->no_show) {
            $this->service()->markNoShow($record->patient, $record->vaccine);
            $message = 'Marked as no-show.';
        } elseif (! $mark && $record->no_show) {
            $this->service()->clearNoShow($record);
            $message = 'No-show cleared.';
        } else {
            $message = $record->no_show ? 'Already marked as no-show.' : 'Nothing to clear.';
        }

        return back()->with('success', $message);
    }

    public function householdMatch(Request $request)
    {
        $this->authorizeImmunizations();

        $validated = $request->validate([
            'surname' => ['required', 'string', 'max:255'],
            'zone_id' => ['nullable', 'integer', 'exists:zones,id'],
        ]);

        $query = Household::withCount('patients')
            ->with('zone')
            ->where('family_name_head', 'like', '%'.addcslashes($validated['surname'], '%_').'%');

        if (! empty($validated['zone_id'])) {
            $query->where('zone_id', $validated['zone_id']);
        }

        return response()->json(
            $query->orderBy('family_name_head')->limit(10)->get()
        );
    }

    public function store(Request $request)
    {
        if (! auth()->check() || ! auth()->user()->hasPermission('immunizations')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'vaccine_id' => ['required', 'integer', 'exists:vaccines_lookup,id'],
            'dose_number' => ['required', 'integer', 'min:1', 'max:99'],
            'date_given' => ['required', 'date', 'before_or_equal:today'],
            'administered_by' => ['nullable', 'integer', 'exists:health_workers,id'],
            'next_due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'date_given.before_or_equal' => 'Date given cannot be in the future.',
        ]);

        $patient = DB::table('patients')->where('id', $validated['patient_id'])->first();
        $age = Carbon::parse($patient->date_of_birth)->age;
        $isChild = $age < 18;
        $allowedCategories = $isChild ? ['Child', 'Both'] : ['Adult', 'Both'];

        $vaccine = DB::table('vaccines_lookup')->where('id', $validated['vaccine_id'])->first();
        if (! in_array($vaccine->category, $allowedCategories)) {
            return back()
                ->withErrors(['vaccine_id' => 'This vaccine is not appropriate for the patient\'s age group.'])
                ->withInput();
        }

        DB::table('immunization_records')->insert([
            'patient_id' => $validated['patient_id'],
            'vaccine_id' => $validated['vaccine_id'],
            'dose_number' => $validated['dose_number'],
            'date_given' => $validated['date_given'],
            'administered_by' => $validated['administered_by'] ?? null,
            'next_due_date' => $validated['next_due_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('immunizations.patient', $validated['patient_id'])
            ->with('success', 'Immunization record saved.');
    }

    private function legacyDueQueue(?int $zoneId)
    {
        $latestRecordPerPatient = DB::table('immunization_records')
            ->select('patient_id', DB::raw('MAX(date_given) as latest_date_given'))
            ->groupBy('patient_id');

        $query = DB::table('immunization_records as ir')
            ->joinSub($latestRecordPerPatient, 'lr', function ($join) {
                $join->on('ir.patient_id', '=', 'lr.patient_id')
                    ->on('ir.date_given', '=', 'lr.latest_date_given');
            })
            ->join('patients', 'ir.patient_id', '=', 'patients.id')
            ->join('vaccines_lookup', 'ir.vaccine_id', '=', 'vaccines_lookup.id')
            ->select(
                'patients.id as patient_id',
                'patients.first_name',
                'patients.last_name',
                'ir.next_due_date',
                'vaccines_lookup.vaccine_name',
                'ir.dose_number'
            )
            ->whereNotNull('ir.next_due_date')
            ->orderBy('ir.next_due_date')
            ->orderBy('patients.last_name');

        if ($zoneId !== null) {
            $query->join('households', 'patients.household_id', '=', 'households.id')
                ->where('households.zone_id', $zoneId);
        }

        return $query;
    }

    private function authorizeImmunizations(): void
    {
        if (! auth()->check() || ! auth()->user()->hasPermission('immunizations')) {
            abort(403, 'Unauthorized');
        }
    }

    private function resolveMode(Request $request): string
    {
        $mode = $request->session()->get('immunizations.mode', 'child');

        if ($request->has('mode') && in_array($request->input('mode'), ['child', 'adult'], true)) {
            $mode = $request->input('mode');
            $request->session()->put('immunizations.mode', $mode);
        }

        return in_array($mode, ['child', 'adult'], true) ? $mode : 'child';
    }

    private function service(): ChildImmunizationService
    {
        return app(ChildImmunizationService::class);
    }

    private function currentWorkerId(): ?int
    {
        $workerId = DB::table('health_workers')->where('user_id', auth()->id())->value('id');

        return $workerId !== null ? (int) $workerId : null;
    }

    private function vaccineMatchesPatientAge(Patient $patient, Vaccine $vaccine): bool
    {
        $isChild = ($patient->age ?? 0) < 18;
        $allowed = $isChild ? ['Child', 'Both'] : ['Adult', 'Both'];

        return in_array($vaccine->category, $allowed, true);
    }
}
