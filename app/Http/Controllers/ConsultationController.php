<?php

namespace App\Http\Controllers;

use App\Enums\ConsultationStatus;
use App\Http\Requests\AddDiagnosisRequest;
use App\Http\Requests\AddPrescriptionRequest;
use App\Http\Requests\FinalizeConsultationRequest;
use App\Http\Requests\ReferralRequest;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\UpdateConsultationRequest;
use App\Http\Requests\VitalsRequest;
use App\Models\Consultation;
use App\Models\HealthWorker;
use App\Models\Patient;
use App\Services\ConsultationHandoutService;
use App\Services\ConsultationQueryService;
use App\Services\ConsultationService;
use App\Services\ConsultationWorkspaceService;
use App\Services\MaternalQueueAggregatorService;
use App\Services\ReferralService;
use App\Services\VitalsService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

class ConsultationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizePermission('consultations');

        $consultations = ConsultationQueryService::paginateIndex($request->only(['sort', 'query', 'date_from', 'date_to']), auth()->user());

        $consultationIds = $consultations->pluck('id')->toArray();
        $diagnosisByConsultation = ConsultationQueryService::diagnosesByConsultation($consultationIds);
        $treatmentByConsultation = ConsultationQueryService::treatmentsByConsultation($consultationIds);

        ['total' => $totalConsultations, 'thisWeek' => $thisWeekCount, 'completed' => $completedCount] = ConsultationQueryService::indexStats(auth()->user());

        return view('consultations.index', [
            'consultations' => $consultations,
            'diagnosisByConsultation' => $diagnosisByConsultation,
            'treatmentByConsultation' => $treatmentByConsultation,
            'totalConsultations' => $totalConsultations,
            'thisWeekCount' => $thisWeekCount,
            'completedCount' => $completedCount,
            'currentSort' => $request->input('sort', 'newest'),
        ]);
    }

    public function liveRequests(Request $request): JsonResponse
    {
        $user = auth()->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $user->hasPermission('consultations')) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $data = ConsultationQueryService::nextUnnotifiedDoctorReview();

        $response = $data === null
            ? ['hasRequest' => false]
            : ['hasRequest' => true, 'request' => $data];

        if (config('features.maternal_tabbed_hub') && $user->hasPermission('maternal.view_queues')) {
            $aggregator = app(MaternalQueueAggregatorService::class);
            $items = $aggregator->aggregate();
            $response['queue_counts'] = [
                'all' => $items->count(),
                'prenatal' => $items->where('program_type', 'prenatal')->count(),
                'postnatal' => $items->where('program_type', 'postnatal')->count(),
                'fp' => $items->where('program_type', 'fp')->count(),
                'watchlist' => $items->where('risk_level', 'high')->count(),
            ];
            $response['queue_version_hash'] = md5(serialize(
                $items->map(fn ($i) => $i->patient_id.':'.$i->program_type)->toArray()
            ));
        }

        return response()->json($response);
    }

    // 1. Show the Admission Form (Triage) — modal partial via AJAX; redirect for direct navigation
    public function create(Request $request, Patient $patient): View|RedirectResponse
    {
        $previousVitals = ConsultationQueryService::previousVitalsFor($patient);

        if ($request->ajax() || $request->wantsJson()) {
            return view('consultations.partials.create-modal', compact('patient', 'previousVitals'));
        }

        return redirect()
            ->back(fallback: route('patients.show', $patient->id))
            ->with('open_consultation_for', $patient->id);
    }

    // 2. Save the Data (Triage Save)
    public function store(StoreConsultationRequest $request, Patient $patient): RedirectResponse
    {

        $worker = $this->currentWorker();
        $result = ConsultationService::start($patient, $request->validated(), $worker);

        $redirect = redirect()->route('patients.show', $patient->id)
            ->with('success', 'Consultation started. Patient is awaiting nurse intake validation.');

        if ($result['referralId']) {
            $redirect->with('print_referral_id', $result['referralId']);
        }

        return $redirect;
    }

    // 3. Show the Doctor's Workspace (View Consultation)
    public function show(Consultation $consultation): View
    {
        $this->authorizePermission('consultations');

        return view('consultations.show', ConsultationWorkspaceService::data(
            $consultation,
            auth()->user()->healthWorker
        ));
    }

    public function acknowledgeIntake(Consultation $consultation): RedirectResponse
    {
        $this->authorizePermission('consultations');

        $worker = $this->currentWorker();
        if (! $worker->isNurse()) {
            abort(403, 'Only nurses can acknowledge intake.');
        }

        if ($consultation->status !== ConsultationStatus::NurseReview->value) {
            return redirect()->back()->withErrors([
                'intake' => 'This consultation is not awaiting nurse validation.',
            ]);
        }

        ConsultationService::acknowledgeIntake($consultation, $worker);

        return redirect()->route('consultations.show', $consultation->id)
            ->with('success', 'Intake acknowledged. Patient is now in the doctor queue.');
    }

    public function cancelIntake(Consultation $consultation): RedirectResponse
    {
        $this->authorizePermission('consultations');

        $worker = $this->currentWorker();
        if (! $worker->isNurse()) {
            abort(403, 'Only nurses can cancel intake requests.');
        }

        if ($consultation->status !== ConsultationStatus::NurseReview->value) {
            return redirect()->back()->withErrors([
                'intake' => 'Only consultations awaiting nurse validation can be canceled.',
            ]);
        }

        ConsultationService::cancel($consultation);

        return redirect()->route('dashboard')
            ->with('success', 'Intake canceled successfully.');
    }

    public function printHandout(Consultation $consultation): View
    {
        $this->guardHandoutAccess($consultation);

        return view('consultations.handout', ConsultationHandoutService::data($consultation));
    }

    public function downloadHandoutPdf(Consultation $consultation): PdfBuilder
    {
        $this->guardHandoutAccess($consultation);

        $data = ConsultationHandoutService::data($consultation);
        $filename = 'iClinicSys-Handout-C'.str_pad((string) $data['consultation']->id, 4, '0', STR_PAD_LEFT).'.pdf';

        return Pdf::view('consultations.handout-pdf', $data)
            ->format(Format::A4)
            ->margins(6, 6, 6, 6)
            ->inline($filename);
    }

    public function retakeVitals(VitalsRequest $request, Consultation $consultation): RedirectResponse
    {
        $this->authorizePermission('consultations');

        $worker = $this->currentWorker();

        if ($error = ConsultationService::clinicalReviewError($consultation)) {
            return redirect()->back()->withErrors(['consultation' => $error]);
        }

        VitalsService::recordClinical($consultation, $request->validated(), $worker);

        return redirect()->route('consultations.show', $consultation->id)
            ->with('success', 'Clinical vitals saved as a new version.');
    }

    public function updateVitalVersion(VitalsRequest $request, Consultation $consultation, int $vitalId): RedirectResponse
    {
        $this->authorizePermission('consultations');

        if (! VitalsService::updateVersion($consultation, (int) $vitalId, $request->validated())) {
            abort(404, 'Vitals version not found for this consultation.');
        }

        return redirect()->route('consultations.show', $consultation->id)
            ->with('success', 'Vitals version updated successfully.');
    }

    public function deleteVitalVersion(Consultation $consultation, int $vitalId): RedirectResponse
    {
        $this->authorizePermission('consultations');

        $result = VitalsService::deleteVersion($consultation, (int) $vitalId);

        if ($result->notFound) {
            abort(404, 'Vitals version not found for this consultation.');
        }

        if ($result->error) {
            return redirect()->route('consultations.show', $consultation->id)
                ->withErrors(['vitals' => $result->error]);
        }

        return redirect()->route('consultations.show', $consultation->id)
            ->with('success', 'Vitals version deleted successfully.');
    }

    // 4. Save a Diagnosis (Doctor's Action)
    public function addDiagnosis(AddDiagnosisRequest $request, Consultation $consultation): RedirectResponse
    {
        $this->authorizePermission('consultations');

        $worker = $this->currentWorker();
        if (! $worker->isDoctor()) {
            abort(403, 'Only doctors can add diagnoses.');
        }

        if ($error = ConsultationService::clinicalReviewError($consultation)) {
            return redirect()->back()->withErrors(['consultation' => $error]);
        }

        $autoCompleted = ConsultationService::recordDiagnosis($consultation, $request->validated(), $worker);

        return redirect()->back()->with(
            'success',
            $autoCompleted ? 'Diagnosis added. Consultation marked as completed.' : 'Diagnosis added successfully!'
        );
    }

    public function referralContext(Consultation $consultation): JsonResponse
    {
        $this->authorizePermission('consultations');

        $context = ReferralService::context($consultation);

        return response()->json($context);
    }

    public function refer(ReferralRequest $request, Consultation $consultation): RedirectResponse
    {
        $this->authorizePermission('consultations');

        $worker = $this->currentWorker();
        if (! $worker->isClinical()) {
            abort(403, 'Only Nurse and Doctor roles can refer patients.');
        }

        if (! in_array($consultation->status, [ConsultationStatus::NurseReview->value, ConsultationStatus::DoctorReview->value, ConsultationStatus::InProgress->value], true)) {
            return redirect()->back()->withErrors(['referral' => 'Referral can only be submitted while the consultation is active or pending validation.']);
        }

        ConsultationService::refer($consultation, $request->validated());

        return redirect()->route('consultations.show', $consultation->id)
            ->with('success', 'Referral request submitted and consultation marked as referred.');
    }

    public function finalizeConsultation(FinalizeConsultationRequest $request, Consultation $consultation): RedirectResponse
    {
        $this->authorizePermission('consultations');

        if ($error = ConsultationService::clinicalReviewError($consultation)) {
            return redirect()->back()->withErrors(['consultation' => $error]);
        }

        if (! ConsultationService::hasDiagnosis((int) $consultation->id)) {
            return redirect()->route('consultations.show', $consultation->id)
                ->withErrors(['diagnosis' => 'Add at least one diagnosis before finalizing consultation.']);
        }

        try {
            $status = ConsultationService::finalize($consultation, $request->validated(), $this->currentWorker());
        } catch (DomainException $e) {
            return redirect()->back()->withErrors(['refer_to_higher_facility' => $e->getMessage()])->withInput();
        }

        return redirect()->route('consultations.show', $consultation->id)
            ->with('success', $status === ConsultationStatus::Referred->value
                ? 'Consultation finalized and marked as referred.'
                : 'Consultation finalized successfully.');
    }

    // 5. Save a Prescription
    public function addPrescription(AddPrescriptionRequest $request, Consultation $consultation): RedirectResponse
    {
        $this->authorizePermission('consultations');

        $worker = $this->currentWorker();
        if (! $worker->isDoctor()) {
            abort(403, 'Only doctors can add prescriptions.');
        }

        if ($error = ConsultationService::clinicalReviewError($consultation)) {
            return redirect()->back()->withErrors(['consultation' => $error]);
        }

        $autoCompleted = ConsultationService::recordPrescription($consultation, $request->validated(), $worker);

        return redirect()->back()->with(
            'success',
            $autoCompleted ? 'Prescription added. Consultation marked as completed.' : 'Prescription added successfully.'
        );
    }

    // Edit Consultation (Quick edit for notes/treatments)
    public function edit(Consultation $consultation): View
    {
        $this->authorizePermission('consultations');

        return view('consultations.edit', [
            'consultation' => $consultation,
            'patient' => Patient::query()->find($consultation->patient_id),
            'diagnoses' => ConsultationQueryService::diagnosesForEdit($consultation),
            'prescriptions' => ConsultationQueryService::prescriptionsForEdit($consultation),
        ]);
    }

    public function update(UpdateConsultationRequest $request, Consultation $consultation): RedirectResponse
    {
        $this->authorizePermission('consultations');

        if ($request->has('notes')) {
            ConsultationService::updateNotes($consultation, $request->input('notes'));
        }

        return redirect()->route('consultations.show', $consultation->id)->with('success', 'Consultation updated successfully.');
    }

    public function deleteDiagnosis(Request $request, Consultation $consultation, int $diagnosisId): JsonResponse|RedirectResponse
    {
        $this->authorizePermission('consultations');

        if (! ConsultationService::deleteDiagnosis($consultation, (int) $diagnosisId)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Diagnosis not found'], 404);
            }
            abort(404, 'Diagnosis not found');
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Diagnosis deleted successfully']);
        }

        return redirect()->route('consultations.edit', $consultation->id)->with('success', 'Diagnosis deleted successfully.');
    }

    public function deletePrescription(Request $request, Consultation $consultation, int $prescriptionId): JsonResponse|RedirectResponse
    {
        $this->authorizePermission('consultations');

        if (! ConsultationService::deletePrescription($consultation, (int) $prescriptionId)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Prescription not found'], 404);
            }
            abort(404, 'Prescription not found');
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Prescription deleted successfully']);
        }

        return redirect()->route('consultations.edit', $consultation->id)->with('success', 'Prescription deleted successfully.');
    }

    private function currentWorker(): HealthWorker
    {
        $worker = auth()->user()->healthWorker;

        if ($worker === null) {
            abort(403, 'No health worker profile is linked to this user.');
        }

        return $worker;
    }

    private function guardHandoutAccess(Consultation $consultation): void
    {
        $this->authorizePermission('consultations');

        if (! auth()->user()->canPrintHandout()) {
            abort(403, 'You do not have permission to print consultation handouts.');
        }

        if (! in_array($consultation->status, ConsultationStatus::terminalValues(), true)) {
            abort(403, 'Print handout is available only for completed consultations.');
        }
    }
}
