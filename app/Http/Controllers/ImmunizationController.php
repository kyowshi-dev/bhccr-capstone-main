<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkerId;
use App\Http\Requests\AdministerVaccineRequest;
use App\Http\Requests\MarkNoShowRequest;
use App\Http\Requests\StoreInfantWithHouseholdRequest;
use App\Models\Household;
use App\Models\Patient;
use App\Models\Vaccine;
use App\Models\Zone;
use App\Services\ChildImmunizationService;
use App\Services\ImmunizationQueryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ImmunizationController extends Controller
{
    use ResolvesWorkerId;

    public function __construct(
        private readonly ChildImmunizationService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeImmunizations();

        $mode = $this->resolveMode($request);
        $zoneId = $request->filled('zone_id') ? (int) $request->input('zone_id') : null;
        $date = $request->filled('date') ? $request->input('date') : Carbon::today()->toDateString();

        $categories = $mode === 'child' ? ['Child', 'Both'] : ['Adult', 'Both'];

        $queues = [];
        foreach (['due', 'overdue', 'no_show'] as $key) {
            $queues[$key] = $this->service->queue($key, $zoneId, $key === 'due' ? $date : null, $categories);
        }

        $dueTodayCount = $queues['due']->map(fn (array $entry) => $entry['patient']->id)->unique()->count();
        $overdueCount = $queues['overdue']->map(fn (array $entry) => $entry['patient']->id)->unique()->count();
        $noShowCount = $queues['no_show']->count();

        $dueTodayPatients = $queues['due']
            ->map(fn (array $entry) => (object) [
                'patient_id' => $entry['patient']->id,
                'first_name' => $entry['patient']->first_name,
                'last_name' => $entry['patient']->last_name,
                'due_date' => $entry['due_date']->toDateString(),
                'dose_number' => $entry['dose_number'],
                'vaccine_name' => $entry['vaccine']->vaccine_name,
            ])
            ->values();

        $recentRecords = $this->service->withNextDue(ImmunizationQueryService::recentRecords());

        $infantStats = ImmunizationQueryService::infantStats($zoneId);
        ['totalGiven' => $totalGiven, 'patientsWithRecords' => $patientsWithRecords] = ImmunizationQueryService::overallStats();

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
            'noShowCount' => $noShowCount,
            'infantCoveragePercent' => $infantStats['infantCoveragePercent'],
            'infantTotal' => $infantStats['infantTotal'],
            'totalGiven' => $totalGiven,
            'patientsWithRecords' => $patientsWithRecords,
        ]);
    }

    public function forPatient($id): View
    {
        $this->authorizeImmunizations();

        $patient = Patient::with(['household', 'immunizationRecords'])->findOrFail($id);

        $isChild = $patient->age < 18;
        $allowedCategories = $isChild ? ['Child', 'Both'] : ['Adult', 'Both'];

        $records = ImmunizationQueryService::recordsForPatient($id);
        $vaccines = ImmunizationQueryService::vaccinesFor($allowedCategories);
        $healthWorkers = ImmunizationQueryService::healthWorkers();

        $recordsByVaccine = $records->groupBy('vaccine_id');
        $schedule = $vaccines->map(function ($vaccine) use ($recordsByVaccine) {
            $doses = $recordsByVaccine->get($vaccine->id, collect());
            $latestDose = $doses->sortByDesc('date_given')->first();

            return (object) [
                'vaccine' => $vaccine,
                'doses_given' => $doses->count(),
                'latest_date' => $latestDose?->date_given,
                'latest_dose_number' => $latestDose?->dose_number,
            ];
        });

        $vaccineModels = Vaccine::whereIn('category', $allowedCategories)->with('schedules')->get();
        $statuses = $vaccineModels->mapWithKeys(fn (Vaccine $v) => [$v->id => $this->service->statusFor($patient, $v)]);
        $eligibility = $vaccineModels->mapWithKeys(fn (Vaccine $v) => [$v->id => $this->service->eligibility($patient, $v)]);
        $schedulesByVaccine = $vaccineModels->mapWithKeys(fn (Vaccine $v) => [$v->id => $v->schedules]);

        $currentWorkerId = (int) $this->currentWorkerId();

        $noShowEvents = $vaccineModels->mapWithKeys(
            fn (Vaccine $v) => [$v->id => $this->service->unresolvedMissed($patient, $v)]
        );

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
            'noShowEvents' => $noShowEvents,
            'isImmunizationEnrolled' => $patient->is_immunization_enrolled,
        ]);
    }

    public function administer(AdministerVaccineRequest $request, $id): RedirectResponse
    {
        $patient = Patient::findOrFail($id);

        $vaccine = Vaccine::findOrFail($request->input('vaccine_id'));

        if (! $this->service->vaccineMatchesAge($patient, $vaccine)) {
            return back()->withErrors(['vaccine_id' => 'This vaccine is not appropriate for this patient.'])->withInput();
        }

        try {
            $record = $this->service->administer($patient, $vaccine, [
                'date_given' => $request->input('date_given'),
                'temp_recorded' => $request->input('temp_recorded'),
                'notes' => $request->input('notes'),
                'override_reason' => $request->input('override_reason'),
                'administered_by' => $this->currentWorkerId(),
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $next = '';
        $nextDue = $this->service->nextDoseDate($patient, $vaccine);

        if ($nextDue !== null && $nextDue->gt($record->date_given)) {
            $next = ' Next dose due '.$nextDue->format('M j, Y').'.';
        }

        return redirect()
            ->route('immunizations.patient', $patient->id)
            ->with('success', $vaccine->vaccine_name.' recorded (dose '.$record->dose_number.').'.$next);
    }

    /**
     * Quick "Mark as done" for patients who received the dose elsewhere
     * (hospital, another facility). Records with today's date when the
     * actual date is unknown.
     */
    public function markGiven($id, Vaccine $vaccine): RedirectResponse
    {
        $patient = Patient::findOrFail($id);

        if (! $this->service->vaccineMatchesAge($patient, $vaccine)) {
            return back()->withErrors(['vaccine_id' => 'This vaccine is not appropriate for this patient.']);
        }

        try {
            $record = $this->service->administer($patient, $vaccine, [
                'date_given' => null,
                'temp_recorded' => null,
                'notes' => 'Marked as done — administered elsewhere',
                'override_reason' => null,
                'administered_elsewhere' => true,
                'administered_by' => $this->currentWorkerId(),
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('immunizations.index')
            ->with('success', $vaccine->vaccine_name.' (dose '.$record->dose_number.') marked as done — recorded as '.$record->date_given->format('M j, Y').'.');
    }

    public function enrollInfant(StoreInfantWithHouseholdRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        if ($request->boolean('create_household')) {
            if ($user->isZoneScoped() && ! in_array((int) $validated['zone_id'], $user->accessibleZoneIds(), true)) {
                abort(403, 'You cannot enroll an infant outside your assigned zones.');
            }
        } elseif (! $user->canAccessHousehold((int) $validated['household_id'])) {
            abort(403, 'This household is outside your assigned zones.');
        }

        try {
            $patient = $this->service->enrollInfant($validated);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('immunizations.patient', $patient->id)
            ->with('success', 'Infant enrolled ('.$patient->first_name.' '.$patient->last_name.').');
    }

    public function toggleNoShow(MarkNoShowRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $patient = Patient::findOrFail($validated['patient_id']);

        $vaccine = Vaccine::findOrFail($validated['vaccine_id']);

        if ($validated['no_show']) {
            $this->service->markNoShow($patient, $vaccine, $validated['dose_number'] ?? null);
            $message = $patient->first_name.' '.$patient->last_name.' marked as no-show.';
        } else {
            $cleared = $this->service->clearNoShow($patient, $vaccine);
            $message = $cleared !== null
                ? 'No-show cleared; it stays in the patient history.'
                : 'No unresolved no-show to clear.';
        }

        return back()->with('success', $message);
    }

    public function enroll(Patient $patient): RedirectResponse
    {
        $this->authorizeImmunizations();

        $this->service->enrollPatient($patient);

        return back()->with('success', $patient->first_name.' '.$patient->last_name.' enrolled in immunization program.');
    }

    public function householdMatch(Request $request): JsonResponse
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

        if (auth()->user()->isZoneScoped()) {
            $query->whereIn('zone_id', auth()->user()->accessibleZoneIds());
        }

        $results = $query->orderBy('family_name_head')->limit(10)->get();

        return response()->json(
            $results->map(fn (Household $household) => [
                'id' => $household->id,
                'family_name_head' => $household->family_name_head,
                'zone_id' => $household->zone_id,
                'zone' => ['zone_number' => $household->zone->zone_number],
                'patients_count' => $household->patients_count,
            ])->values()
        );
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
}
