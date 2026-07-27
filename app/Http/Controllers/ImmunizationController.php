<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImmunizationController extends Controller
{
    public function index()
    {
        if (! auth()->user()->hasPermission('immunizations')) {
            abort(403, 'Unauthorized');
        }

        $today = Carbon::today()->toDateString();

        $latestRecordPerPatient = DB::table('immunization_records')
            ->select('patient_id', DB::raw('MAX(date_given) as latest_date_given'))
            ->groupBy('patient_id');

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

        $dueQueue = DB::table('immunization_records as ir')
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

        $infantCutoff = Carbon::today()->subYear()->toDateString();
        $infantTotal = DB::table('patients')
            ->where('date_of_birth', '>=', $infantCutoff)
            ->count();
        $infantWithAnyDose = DB::table('immunization_records')
            ->join('patients', 'immunization_records.patient_id', '=', 'patients.id')
            ->where('patients.date_of_birth', '>=', $infantCutoff)
            ->distinct('immunization_records.patient_id')
            ->count('immunization_records.patient_id');

        $infantCoveragePercent = $infantTotal > 0
            ? (int) round(($infantWithAnyDose / $infantTotal) * 100)
            : null;

        $totalGiven = DB::table('immunization_records')->count();
        $patientsWithRecords = DB::table('immunization_records')->distinct('patient_id')->count('patient_id');

        return view('immunizations.index', [
            'recentRecords' => $recentRecords,
            'dueTodayPatients' => $dueTodayPatients,
            'dueTodayCount' => $dueTodayCount,
            'overdueCount' => $overdueCount,
            'infantCoveragePercent' => $infantCoveragePercent,
            'infantTotal' => $infantTotal,
            'totalGiven' => $totalGiven,
            'patientsWithRecords' => $patientsWithRecords,
        ]);
    }

    public function forPatient($id)
    {
        if (! auth()->user()->hasPermission('immunizations')) {
            abort(403, 'Unauthorized');
        }

        $patient = DB::table('patients')
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->where('patients.id', $id)
            ->select('patients.*', 'households.family_name_head', 'households.zone_id')
            ->first();

        if (! $patient) {
            abort(404, 'Patient not found');
        }

        $patient->age = Carbon::parse($patient->date_of_birth)->age;

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

        $currentWorkerId = DB::table('health_workers')->where('user_id', auth()->id())->value('id');

        return view('immunizations.patient', [
            'patient' => $patient,
            'records' => $records,
            'vaccines' => $vaccines,
            'schedule' => $schedule,
            'healthWorkers' => $healthWorkers,
            'currentWorkerId' => $currentWorkerId,
        ]);
    }

    public function administer(Request $request, $id)
    {
        if (! auth()->user()->hasPermission('immunizations')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'vaccine_id' => ['required', 'integer', 'exists:vaccines_lookup,id'],
            'dose_number' => ['nullable', 'integer', 'min:1', 'max:99'],
            'date_given' => ['nullable', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $patient = DB::table('patients')->where('id', $id)->first();
        if (! $patient) {
            abort(404, 'Patient not found');
        }

        $age = Carbon::parse($patient->date_of_birth)->age;
        $isChild = $age < 18;
        $allowedCategories = $isChild ? ['Child', 'Both'] : ['Adult', 'Both'];

        $vaccine = DB::table('vaccines_lookup')->where('id', $validated['vaccine_id'])->first();
        if (! in_array($vaccine->category, $allowedCategories, true)) {
            return back()->withErrors(['vaccine_id' => 'This vaccine is not appropriate for the patient\'s age group.']);
        }

        $lastDose = DB::table('immunization_records')
            ->where('patient_id', $id)
            ->where('vaccine_id', $validated['vaccine_id'])
            ->orderByDesc('dose_number')
            ->value('dose_number');

        $doseNumber = $validated['dose_number'] ?? ((int) $lastDose + 1);
        $workerId = DB::table('health_workers')->where('user_id', auth()->id())->value('id');

        DB::table('immunization_records')->insert([
            'patient_id' => $id,
            'vaccine_id' => $validated['vaccine_id'],
            'dose_number' => $doseNumber,
            'date_given' => $validated['date_given'] ?? Carbon::today()->toDateString(),
            'administered_by' => $workerId,
            'next_due_date' => null,
            'notes' => $validated['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('immunizations.patient', $id)
            ->with('success', $vaccine->vaccine_name.' marked as administered (dose '.$doseNumber.').');
    }

    public function store(Request $request)
    {
        if (! auth()->user()->hasPermission('immunizations')) {
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

        // Get patient and vaccine for age check
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
}
