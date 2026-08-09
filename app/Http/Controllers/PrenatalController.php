<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkerId;
use App\Http\Requests\AddPrenatalVisitRequest;
use App\Http\Requests\ObstetricHistoryRequest;
use App\Http\Requests\RegisterPregnancyRequest;
use App\Http\Requests\UpdatePregnancyRequest;
use App\Http\Requests\UpdatePrenatalVisitRequest;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Models\PrenatalVisit;
use App\Models\Zone;
use App\Services\MaternalProfileService;
use App\Services\MaternalQueryService;
use App\Services\PregnancyService;
use App\Services\PrenatalVisitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PrenatalController extends Controller
{
    use ResolvesWorkerId;

    public function __construct(
        private readonly PregnancyService $pregnancyService,
        private readonly PrenatalVisitService $visitService,
        private readonly MaternalProfileService $profileService,
        private readonly MaternalQueryService $query,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeMaternal();

        $pregnancies = $this->query->activePregnancies([
            'zone_id' => $request->filled('zone_id') ? (int) $request->input('zone_id') : null,
            'search' => $request->input('search'),
        ]);

        return view('maternal.prenatal.index', [
            'pregnancies' => $pregnancies,
            'zones' => Zone::orderBy('zone_number')->get(),
            'zoneId' => $request->input('zone_id'),
            'search' => $request->input('search'),
        ]);
    }

    public function patient(Patient $patient): View
    {
        $this->authorizeMaternal();

        return view('maternal.prenatal.patient', [
            'patient' => $patient->load(['household.zone', 'maternalProfile']),
            'pregnancies' => $this->query->pregnanciesForPatient($patient),
            'profile' => $patient->maternalProfile,
            'consultations' => $patient->consultations()
                ->orderByDesc('created_at')
                ->limit(30)
                ->get(),
        ]);
    }

    public function store(RegisterPregnancyRequest $request, Patient $patient): RedirectResponse
    {
        try {
            $pregnancy = $this->pregnancyService->register(
                $patient,
                $request->validated(),
                $this->currentWorkerId()
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        if ($request->boolean('save_profile') && $request->has('maternal_profile')) {
            $this->profileService->upsert($patient, array_filter($request->input('maternal_profile', [])));
        }

        return redirect()
            ->route('maternal.prenatal.patient', $patient->id)
            ->with('success', 'Pregnancy registered. EDC '.$pregnancy->edc->format('M j, Y').'.');
    }

    public function updateProfile(ObstetricHistoryRequest $request, Patient $patient): RedirectResponse
    {
        $this->profileService->upsert($patient, $request->validated());

        return redirect()
            ->route('maternal.prenatal.patient', $patient->id)
            ->with('success', 'Obstetric history updated.');
    }

    public function updatePregnancy(UpdatePregnancyRequest $request, Pregnancy $pregnancy): RedirectResponse
    {
        $this->pregnancyService->update($pregnancy, $request->validated());

        return redirect()
            ->route('maternal.prenatal.patient', $pregnancy->patient_id)
            ->with('success', 'Pregnancy updated.');
    }

    public function addVisit(AddPrenatalVisitRequest $request, Pregnancy $pregnancy): RedirectResponse
    {
        $this->visitService->add($pregnancy, $request->validated(), $this->currentWorkerId());

        return redirect()
            ->route('maternal.prenatal.patient', $pregnancy->patient_id)
            ->with('success', 'Prenatal visit recorded.');
    }

    public function updateVisit(UpdatePrenatalVisitRequest $request, PrenatalVisit $visit): RedirectResponse
    {
        $this->visitService->update($visit, $request->validated());

        return redirect()
            ->route('maternal.prenatal.patient', $visit->pregnancy->patient_id)
            ->with('success', 'Prenatal visit updated.');
    }

    public function print(Pregnancy $pregnancy): View
    {
        $this->authorizeMaternal();

        return view('maternal.print.prenatal', [
            'pregnancy' => $pregnancy->load(['patient.household.zone', 'visits']),
        ]);
    }

    private function authorizeMaternal(): void
    {
        if (! auth()->check() || ! auth()->user()->hasPermission('maternal')) {
            abort(403, 'Unauthorized');
        }
    }
}
