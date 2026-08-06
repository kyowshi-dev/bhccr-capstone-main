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
use App\Models\Vitals;
use App\Services\ConsultationHandoutService;
use App\Services\ConsultationQueryService;
use App\Services\ConsultationService;
use App\Services\ReferralService;
use App\Services\VitalsService;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\Response;

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
        if (! auth()->user()->hasPermission('consultations')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only fetch consultations waiting for doctor that haven't been notified yet
        $consultation = DB::table('consultations')
            ->where('status', ConsultationStatus::DoctorReview->value)
            ->whereNull('notified_at')  // Only unnotified consultations
            ->orderByDesc('created_at')
            ->first();

        if (! $consultation) {
            return response()->json(['hasRequest' => false]);
        }

        $patient = DB::table('patients')->where('id', $consultation->patient_id)->first();
        $worker = DB::table('health_workers')->where('id', $consultation->worker_id)->first();

        if (! $patient || ! $worker) {
            return response()->json(['hasRequest' => false]);
        }

        // Mark consultation as notified
        DB::table('consultations')
            ->where('id', $consultation->id)
            ->update(['notified_at' => now()]);

        return response()->json([
            'hasRequest' => true,
            'request' => [
                'id' => $consultation->id,
                'open_url' => route('consultations.show', ['consultation' => $consultation->id]),
                'clinic_name' => 'Santa Ana Health Center',
                'worker_name' => trim(($worker->first_name ?? '').' '.($worker->last_name ?? '')),
                'patient_name' => trim(($patient->first_name ?? '').' '.($patient->last_name ?? '')),
                'patient_age' => $patient->date_of_birth ? Carbon::parse($patient->date_of_birth)->age : null,
                'patient_gender' => $patient->sex ?? '',
                'chief_complaint' => $consultation->complaint_text ?? $consultation->chief_complaint ?? 'No reason provided',
            ],
        ]);
    }

    // 1. Show the Admission Form (Triage) — modal partial via AJAX; redirect for direct navigation
    public function create(Request $request, Patient $patient): View|RedirectResponse
    {
        $this->guardPatientAccess($patient);

        $patient->age = Carbon::parse($patient->date_of_birth)->age;

        $previousVitals = DB::table('vitals')
            ->join('consultations', 'vitals.consultation_id', '=', 'consultations.id')
            ->where('consultations.patient_id', $patient->id)
            ->orderByDesc('vitals.created_at')
            ->orderByDesc('vitals.id')
            ->select([
                'vitals.bp_systolic',
                'vitals.bp_diastolic',
                'vitals.temperature_c',
                'vitals.weight_kg',
                'vitals.height_cm',
            ])
            ->first();

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
        $this->guardPatientAccess($patient);

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
        $this->guardConsultationAccess($consultation);

        $patient = DB::table('patients')->find($consultation->patient_id);

        if ($patient) {
            $patient->age = Carbon::parse($patient->date_of_birth)->age;
        }

        $allVitals = Vitals::query()
            ->where('vitals.consultation_id', $consultation->id)
            ->leftJoin('health_workers', 'vitals.captured_by', '=', 'health_workers.id')
            ->orderBy('vitals.created_at')
            ->orderBy('vitals.id')
            ->select(
                'vitals.*',
                'health_workers.first_name as captured_by_first_name',
                'health_workers.last_name as captured_by_last_name',
                'health_workers.role as captured_by_role'
            )
            ->get();

        $triageVitals = $allVitals->firstWhere('phase', 'triage') ?? $allVitals->first();
        $latestVitals = $allVitals->last();
        $vitals = $latestVitals;
        if (! $vitals) {
            $vitals = (object) [
                'bp_systolic' => null,
                'bp_diastolic' => null,
                'temperature_c' => null,
                'weight_kg' => null,
                'height_cm' => null,
                'phase' => 'triage',
            ];
        }

        $currentUserRole = strtolower((string) (auth()->user()->healthWorker?->role ?? ''));

        $canReferExternally = in_array($currentUserRole, ['doctor', 'nurse'], true);
        $canAcknowledgeIntake = $currentUserRole === 'nurse';
        $canAddDiagnosis = $currentUserRole === 'doctor';
        $canAddPrescription = $currentUserRole === 'doctor';

        // 2. Fetch Existing Records (History)
        $existingDiagnoses = ConsultationQueryService::diagnosisRecordsQuery()
            ->where('diagnosis_records.consultation_id', $consultation->id)
            ->select(
                'diagnosis_records.*',
                'diagnosis_lookup.diagnosis_code as diagnosis_code',
                'diagnosis_lookup.diagnosis_name as diagnosis_name',
                DB::raw('(diagnosis_records.diagnosis_id IS NULL) as is_custom')
            )
            ->get();

        $existingPrescriptions = ConsultationQueryService::prescriptionsQuery()
            ->where('prescriptions.consultation_id', $consultation->id)
            ->select(
                'prescriptions.*',
                'medicines_lookup.name as medicine_name',
                DB::raw('(prescriptions.medicine_id IS NULL) as is_custom')
            )
            ->get();

        // 3. NEW: Fetch Dropdown Options (The "Menu" for the Doctor)
        $diagnosisOptions = DB::table('diagnosis_lookup')->orderBy('diagnosis_name')->get();
        $medicineOptions = DB::table('medicines_lookup')->orderBy('name')->get();

        return view('consultations.show', [
            'consultation' => $consultation,
            'patient' => $patient,
            'vitals' => $vitals,
            'triageVitals' => $triageVitals,
            'latestVitals' => $latestVitals,
            'allVitals' => $allVitals,
            'diagnoses' => $existingDiagnoses,
            'prescriptions' => $existingPrescriptions,
            'diagnosisOptions' => $diagnosisOptions,
            'medicineOptions' => $medicineOptions,
            'canReferExternally' => $canReferExternally,
            'canAcknowledgeIntake' => $canAcknowledgeIntake,
            'canAddDiagnosis' => $canAddDiagnosis,
            'canAddPrescription' => $canAddPrescription,
        ]);
    }

    public function acknowledgeIntake(Consultation $consultation): RedirectResponse
    {
        $this->authorizePermission('consultations');
        $this->guardConsultationAccess($consultation);

        $worker = $this->currentWorker();
        if (strtolower((string) $worker->role) !== 'nurse') {
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
        $this->guardConsultationAccess($consultation);

        $worker = $this->currentWorker();
        if (strtolower((string) $worker->role) !== 'nurse') {
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

    public function downloadHandoutPdf(Consultation $consultation): Response
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
        $this->guardConsultationAccess($consultation);

        $worker = $this->currentWorker();

        if ($error = ConsultationService::clinicalReviewError($consultation)) {
            return redirect()->back()->withErrors(['consultation' => $error]);
        }

        VitalsService::recordClinical($consultation, $request->validated(), $worker);

        return redirect()->route('consultations.show', $consultation->id)
            ->with('success', 'Clinical vitals saved as a new version.');
    }

    public function updateVitalVersion(VitalsRequest $request, Consultation $consultation, $vitalId): RedirectResponse
    {
        $this->authorizePermission('consultations');
        $this->guardConsultationAccess($consultation);

        if (! VitalsService::updateVersion($consultation, (int) $vitalId, $request->validated())) {
            abort(404, 'Vitals version not found for this consultation.');
        }

        return redirect()->route('consultations.show', $consultation->id)
            ->with('success', 'Vitals version updated successfully.');
    }

    public function deleteVitalVersion(Consultation $consultation, $vitalId): RedirectResponse
    {
        $this->authorizePermission('consultations');
        $this->guardConsultationAccess($consultation);

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
        $this->guardConsultationAccess($consultation);

        $worker = $this->currentWorker();
        if (strtolower((string) $worker->role) !== 'doctor') {
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
        $this->guardConsultationAccess($consultation);

        $context = ReferralService::context($consultation);

        if ($context === null) {
            abort(404, 'Patient not found');
        }

        return response()->json($context);
    }

    public function refer(ReferralRequest $request, Consultation $consultation): RedirectResponse
    {
        $this->authorizePermission('consultations');
        $this->guardConsultationAccess($consultation);

        $worker = $this->currentWorker();
        if (! in_array(strtolower((string) $worker->role), ['doctor', 'nurse'], true)) {
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
        $this->guardConsultationAccess($consultation);

        if ($error = ConsultationService::clinicalReviewError($consultation)) {
            return redirect()->back()->withErrors(['consultation' => $error]);
        }

        $diagnosisCount = DB::table('diagnosis_records')
            ->where('consultation_id', $consultation->id)
            ->count();

        if ($diagnosisCount < 1) {
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
        $this->guardConsultationAccess($consultation);

        $worker = $this->currentWorker();
        if (strtolower((string) $worker->role) !== 'doctor') {
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
        $this->guardConsultationAccess($consultation);

        // Get patient info
        $patient = DB::table('patients')->find($consultation->patient_id);

        // Get diagnoses
        $diagnoses = ConsultationQueryService::diagnosisRecordsQuery()
            ->where('diagnosis_records.consultation_id', $consultation->id)
            ->select(
                'diagnosis_records.id',
                'diagnosis_lookup.diagnosis_name as diagnosis_name',
                'diagnosis_records.remarks'
            )
            ->get();

        // Get prescriptions
        $prescriptions = ConsultationQueryService::prescriptionsQuery()
            ->where('prescriptions.consultation_id', $consultation->id)
            ->select(
                'prescriptions.id',
                'medicines_lookup.name as medicine_name',
                'prescriptions.dosage',
                'prescriptions.frequency',
                'prescriptions.duration',
                'prescriptions.quantity'
            )
            ->get();

        return view('consultations.edit', [
            'consultation' => $consultation,
            'patient' => $patient,
            'diagnoses' => $diagnoses,
            'prescriptions' => $prescriptions,
        ]);
    }

    public function update(UpdateConsultationRequest $request, Consultation $consultation): RedirectResponse
    {
        $this->authorizePermission('consultations');
        $this->guardConsultationAccess($consultation);

        if ($request->has('notes')) {
            ConsultationService::updateNotes($consultation, $request->input('notes'));
        }

        return redirect()->route('consultations.show', $consultation->id)->with('success', 'Consultation updated successfully.');
    }

    public function deleteDiagnosis(Request $request, Consultation $consultation, $diagnosisId): JsonResponse|RedirectResponse
    {
        $this->authorizePermission('consultations');
        $this->guardConsultationAccess($consultation);

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

    public function deletePrescription(Request $request, Consultation $consultation, $prescriptionId): JsonResponse|RedirectResponse
    {
        $this->authorizePermission('consultations');
        $this->guardConsultationAccess($consultation);

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
        $this->guardConsultationAccess($consultation);

        if (! auth()->user()->canPrintHandout()) {
            abort(403, 'You do not have permission to print consultation handouts.');
        }

        if (! in_array($consultation->status, ConsultationStatus::terminalValues(), true)) {
            abort(403, 'Print handout is available only for completed consultations.');
        }
    }

    /**
     * Zone scoping: zone-assigned workers may only act on consultations
     * whose patient belongs to one of their assigned zones.
     */
    private function guardConsultationAccess(Consultation $consultation): void
    {
        if (! auth()->user()->canAccessConsultation($consultation)) {
            abort(403, 'This consultation is outside your assigned zones.');
        }
    }

    /**
     * Zone scoping: zone-assigned workers may only open patients in their
     * assigned zones.
     */
    private function guardPatientAccess(Patient $patient): void
    {
        if (! auth()->user()->canAccessPatient($patient)) {
            abort(403, 'This patient is outside your assigned zones.');
        }
    }
}
