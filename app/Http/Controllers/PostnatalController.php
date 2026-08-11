<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkerId;
use App\Http\Requests\CompletePostpartumVisitRequest;
use App\Http\Requests\StorePostnatalRequest;
use App\Http\Requests\UpdatePostnatalRequest;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Zone;
use App\Services\MaternalIntakeService;
use App\Services\MaternalQueryService;
use App\Services\PostnatalService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PostnatalController extends Controller
{
    use ResolvesWorkerId;

    public function __construct(
        private readonly PostnatalService $service,
        private readonly MaternalQueryService $query,
        private readonly MaternalIntakeService $intakeService,
    ) {}

    public function index(Request $request): View
    {
        $records = $this->query->postnatalRecords($request->only('zone_id', 'search'));

        return view('maternal.postnatal.index', [
            'records' => $records,
            'zones' => Zone::orderBy('zone_number')->get(),
            'zoneId' => $request->input('zone_id'),
            'search' => $request->input('search'),
        ]);
    }

    public function patient(Patient $patient): View
    {
        return view('maternal.postnatal.patient', [
            'patient' => $patient->load(['household.zone']),
            'records' => $this->query->postnatalForPatient($patient),
            'activePregnancies' => $patient->pregnancies()
                ->where('status', 'active')
                ->orderByDesc('lmp')
                ->get(),
            'consultations' => $patient->consultations()
                ->orderByDesc('created_at')
                ->limit(30)
                ->get(),
        ]);
    }

    public function store(StorePostnatalRequest $request, Patient $patient): RedirectResponse
    {
        try {
            $record = $this->service->store($patient, $request->validated(), $this->currentWorkerId());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        $child = $record->fresh()?->childPatient;

        $childMessage = $child !== null
            ? ' Child enrolled as '.fullName($child->last_name, $child->first_name, $child->middle_name, $child->suffix).'.'
            : '';

        return redirect()
            ->route('maternal.postnatal.patient', $patient->id)
            ->with('success', 'Postnatal record saved.'.$childMessage);
    }

    public function update(UpdatePostnatalRequest $request, PostnatalRecord $record): RedirectResponse
    {
        try {
            $this->service->update($record, $request->validated());
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('maternal.postnatal.patient', $record->patient_id)
            ->with('success', 'Postnatal record updated.');
    }

    public function completePostpartumVisit(CompletePostpartumVisitRequest $request, PostnatalRecord $record): RedirectResponse
    {
        $slot = $request->input('slot');
        $date = Carbon::parse($request->input('date'))->startOfDay();

        if ($date->lt(Carbon::parse($record->delivery_date)->startOfDay())) {
            return redirect()
                ->route('maternal.postnatal.patient', $record->patient_id)
                ->withErrors([
                    'slot' => 'The visit date cannot be before the delivery date.',
                ])->withInput();
        }

        $worker = $this->resolveWorker();
        $patient = $record->patient;
        $pregnancy = $record->pregnancy ?? null;

        $consultationId = $this->intakeService->recordEncounter(
            $patient,
            'Postpartum',
            $request->validated(),
            $worker,
            $pregnancy,
        );

        $record->update([
            $slot => $date->toDateString(),
            'consultation_id' => $consultationId,
        ]);

        return redirect()
            ->route('maternal.postnatal.patient', $record->patient_id)
            ->with('success', 'Postpartum visit recorded.');
    }

    public function print(PostnatalRecord $record): View
    {
        return view('maternal.print.postnatal', [
            'record' => $record->load(['patient.household.zone', 'pregnancy', 'childPatient']),
        ]);
    }
}
