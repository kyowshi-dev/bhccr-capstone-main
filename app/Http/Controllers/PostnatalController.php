<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkerId;
use App\Http\Requests\CompletePostpartumVisitRequest;
use App\Http\Requests\StorePostnatalRequest;
use App\Http\Requests\UpdatePostnatalRequest;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Pregnancy;
use App\Models\Zone;
use App\Services\MaternalIntakeService;
use App\Services\MaternalQueryService;
use App\Services\PostnatalService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $records = $this->query->postnatalRecords($request->only('zone_id', 'search', 'status'));

        return view('maternal.postnatal.index', [
            'records' => $records,
            'zones' => Zone::orderBy('zone_number')->get(),
            'zoneId' => $request->input('zone_id'),
            'search' => $request->input('search'),
            'status' => $request->input('status', 'active'),
        ]);
    }

    public function patient(Patient $patient): View
    {
        return view('maternal.postnatal.patient', [
            'patient' => $patient->load(['household.zone']),
            'records' => $this->query->postnatalForPatient($patient),
            'activePregnancies' => $patient->pregnancies()
                ->where('status', Pregnancy::STATUS_ACTIVE)
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
        $record = $this->service->store($patient, $request->validated(), $this->currentWorkerId());

        $child = $record->fresh()?->childPatient;

        $childMessage = $child !== null
            ? ' Child enrolled as '.fullName($child->last_name, $child->first_name, $child->middle_name, $child->suffix).'.'
            : '';

        return $this->redirectToPatient($record, 'Postnatal record saved.'.$childMessage);
    }

    public function update(UpdatePostnatalRequest $request, PostnatalRecord $record): RedirectResponse
    {
        $this->service->update($record, $request->validated());

        return $this->redirectToPatient($record, 'Postnatal record updated.');
    }

    public function completePostpartumVisit(CompletePostpartumVisitRequest $request, PostnatalRecord $record): RedirectResponse
    {
        $worker = $this->resolveWorker();
        $pregnancy = $record->pregnancy ?? null;

        $consultationId = $this->intakeService->recordEncounter(
            $record->patient,
            'Postpartum',
            $request->validated(),
            $worker,
            $pregnancy,
            $request->integer('consultation_id') ?: null,
        );

        $record->update([
            $request->validated('slot') => Carbon::parse($request->validated('date'))->startOfDay()->toDateString(),
            'consultation_id' => $consultationId,
        ]);

        return $this->redirectToPatient($record, 'Postpartum visit recorded.');
    }

    public function print(PostnatalRecord $record): View
    {
        return view('maternal.print.postnatal', [
            'record' => $record->load(['patient.household.zone', 'pregnancy', 'childPatient']),
        ]);
    }

    private function redirectToPatient(PostnatalRecord $record, string $message): RedirectResponse
    {
        return redirect()
            ->route('maternal.postnatal.patient', $record->patient_id)
            ->with('success', $message);
    }
}
