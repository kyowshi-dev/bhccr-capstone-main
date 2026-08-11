<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkerId;
use App\Http\Requests\AddFamilyPlanningVisitRequest;
use App\Http\Requests\StoreFamilyPlanningClientRequest;
use App\Http\Requests\UpdateFamilyPlanningClientRequest;
use App\Models\FamilyPlanningClient;
use App\Models\Patient;
use App\Models\Zone;
use App\Services\FamilyPlanningService;
use App\Services\MaternalIntakeService;
use App\Services\MaternalQueryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FamilyPlanningController extends Controller
{
    use ResolvesWorkerId;

    public function __construct(
        private readonly FamilyPlanningService $service,
        private readonly MaternalQueryService $query,
        private readonly MaternalIntakeService $intakeService,
    ) {}

    public function index(Request $request): View
    {
        $clients = $this->query->familyPlanningClients($request->only('zone_id', 'search'));

        return view('maternal.family-planning.index', [
            'clients' => $clients,
            'zones' => Zone::orderBy('zone_number')->get(),
            'zoneId' => $request->input('zone_id'),
            'search' => $request->input('search'),
        ]);
    }

    public function patient(Patient $patient): View
    {
        $client = $patient->fpClient;

        return view('maternal.family-planning.patient', [
            'patient' => $patient->load(['household.zone', 'fpClient.visits']),
            'client' => $client,
            'clients' => $patient->fpClients()
                ->with('visits')
                ->orderByDesc('id')
                ->get(),
            'visitHistory' => $client?->visits()->orderByDesc('visit_date')->get() ?? collect(),
            'consultations' => $patient->consultations()
                ->orderByDesc('created_at')
                ->limit(30)
                ->get(),
        ]);
    }

    public function store(StoreFamilyPlanningClientRequest $request, Patient $patient): RedirectResponse
    {
        $this->service->register($patient, $request->validated(), $this->currentWorkerId());

        return redirect()
            ->route('maternal.family-planning.patient', $patient->id)
            ->with('success', 'Family planning client recorded.');
    }

    public function update(UpdateFamilyPlanningClientRequest $request, FamilyPlanningClient $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()
            ->route('maternal.family-planning.patient', $client->patient_id)
            ->with('success', 'Family planning record updated.');
    }

    public function addVisit(AddFamilyPlanningVisitRequest $request, FamilyPlanningClient $client): RedirectResponse
    {
        $worker = $this->resolveWorker();
        $patient = $client->patient;

        $pregnancy = $this->intakeService->activePregnancyFor($patient);

        $consultationId = $this->intakeService->recordEncounter(
            $patient,
            'Family Planning',
            $request->validated(),
            $worker,
            $pregnancy,
        );

        $data = $request->validated();
        $data['consultation_id'] = $consultationId;

        $this->service->addVisit($client, $data, $this->currentWorkerId());

        return redirect()
            ->route('maternal.family-planning.patient', $client->patient_id)
            ->with('success', 'Follow-up visit recorded.');
    }

    public function print(FamilyPlanningClient $client): View
    {
        return view('maternal.print.family-planning', [
            'client' => $client->load(['patient.household.zone', 'visits']),
        ]);
    }
}
