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
use App\Services\ReferralService;
use App\Services\VitalsService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;

class ConsultationController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $query = Consultation::query()
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->join('health_workers', 'consultations.worker_id', '=', 'health_workers.id')
            ->select(
                'consultations.*',
                'patients.first_name as patient_first_name',
                'patients.last_name as patient_last_name',
                'health_workers.first_name as worker_first_name',
                'health_workers.last_name as worker_last_name'
            );

        // Apply sorting based on sort parameter
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('consultations.created_at');
                break;
            case 'patient_name':
                $query->orderBy('patients.last_name')
                    ->orderBy('patients.first_name');
                break;
            case 'status':
                $query->orderBy('consultations.status')
                    ->orderByDesc('consultations.created_at');
                break;
            case 'newest':
            default:
                $query->orderByDesc('consultations.created_at');
                break;
        }

        if ($request->filled('query')) {
            $q = $request->input('query');
            $query->where(function ($qb) use ($q) {
                $qb->where('patients.first_name', 'like', '%'.$q.'%')
                    ->orWhere('patients.last_name', 'like', '%'.$q.'%')
                    ->orWhereRaw($this->dbConcat(['patients.last_name', 'patients.first_name'], ', ').' LIKE ?', ['%'.$q.'%']);
                if (is_numeric($q)) {
                    $qb->orWhere('patients.id', (int) $q);
                }
                if (preg_match('/^PT\s*(\d+)$/i', trim($q), $m)) {
                    $qb->orWhere('patients.id', (int) $m[1]);
                }
                $qb->orWhereExists(function ($ex) use ($q) {
                    $ex->select(DB::raw(1))
                        ->from('diagnosis_records')
                        ->leftJoin('diagnosis_lookup', 'diagnosis_records.diagnosis_id', '=', 'diagnosis_lookup.id')
                        ->whereColumn('diagnosis_records.consultation_id', 'consultations.id')
                        ->where('diagnosis_lookup.diagnosis_name', 'like', '%'.$q.'%');
                });
            });
        }

        if ($request->filled('date_from')) {
            $parsed = Carbon::createFromFormat('d/m/Y', trim($request->input('date_from')));
            if ($parsed !== false) {
                $query->where('consultations.created_at', '>=', $parsed->copy()->startOfDay());
            }
        }
        if ($request->filled('date_to')) {
            $parsed = Carbon::createFromFormat('d/m/Y', trim($request->input('date_to')));
            if ($parsed !== false) {
                $query->where('consultations.created_at', '<=', $parsed->copy()->endOfDay());
            }
        }

        $consultations = $query->paginate(15)->withQueryString();

        $consultationIds = $consultations->pluck('id')->toArray();

        $diagnosisByConsultation = [];
        $treatmentByConsultation = [];
        if (! empty($consultationIds)) {
            $diagnosisRows = $this->diagnosisRecordsQuery()
                ->whereIn('diagnosis_records.consultation_id', $consultationIds)
                ->select(
                    'diagnosis_records.consultation_id',
                    'diagnosis_lookup.diagnosis_name as diagnosis_name',
                    'diagnosis_records.remarks'
                )
                ->orderBy('diagnosis_records.id')
                ->get();
            foreach ($diagnosisRows as $row) {
                $diagnosisByConsultation[$row->consultation_id][] = trim($row->diagnosis_name.($row->remarks ? ' - '.$row->remarks : ''));
            }

            $prescriptionRows = $this->prescriptionsQuery()
                ->whereIn('prescriptions.consultation_id', $consultationIds)
                ->select(
                    'prescriptions.consultation_id',
                    'medicines_lookup.name as medicine_name',
                    'prescriptions.dosage',
                    'prescriptions.duration'
                )
                ->get();
            foreach ($prescriptionRows as $row) {
                $treatmentByConsultation[$row->consultation_id][] = $row->medicine_name.($row->dosage ? ' '.$row->dosage : '').($row->duration ? ', '.$row->duration : '');
            }
        }

        $totalConsultations = Consultation::count();
        $thisWeekCount = Consultation::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $completedCount = Consultation::where('status', ConsultationStatus::Completed->value)->count();

        return view('consultations.index', [
            'consultations' => $consultations,
            'diagnosisByConsultation' => $diagnosisByConsultation,
            'treatmentByConsultation' => $treatmentByConsultation,
            'totalConsultations' => $totalConsultations,
            'thisWeekCount' => $thisWeekCount,
            'completedCount' => $completedCount,
            'currentSort' => $sort,
        ]);
    }

    public function liveRequests(Request $request)
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
    public function create(Request $request, Patient $patient)
    {
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
    public function store(StoreConsultationRequest $request, Patient $patient)
    {
        $validated = $request->validated();
        $worker = $this->currentWorker();

        $consultationId = null;
        $createdReferralId = null;

        DB::transaction(function () use ($validated, $patient, $worker, &$consultationId, &$createdReferralId) {
            $consultationId = DB::table('consultations')->insertGetId([
                'patient_id' => $patient->id,
                'worker_id' => $worker->id,
                'status' => ConsultationStatus::NurseReview->value,
                'nature_of_visit' => $validated['nature_of_visit'],
                'mode_of_transaction' => $validated['mode_of_transaction'],
                'referred_from' => $validated['referred_from'] ?? null,
                'complaint_text' => $validated['chief_complaint'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (! empty($validated['refer_to_higher_facility'])) {
                $createdReferralId = DB::table('outward_referrals')->insertGetId([
                    'consultation_id' => $consultationId,
                    'destination_facility' => $validated['referred_to'],
                    'pertinent_history' => $validated['pertinent_history'],
                    'actions_taken' => $validated['actions_taken'] ?? null,
                    'specific_details' => ReferralService::specificDetails($validated['referral_reasons'] ?? [], $validated['referral_reason_details'] ?? null),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $vitalsPayload = VitalsService::fromInput($validated) + [
                'consultation_id' => $consultationId,
                'phase' => 'triage',
                'captured_by' => $worker->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            DB::table('vitals')->insert($vitalsPayload);
        });

        $redirect = redirect()->route('patients.show', $patient->id)
            ->with('success', 'Consultation started. Patient is awaiting nurse intake validation.');

        if ($createdReferralId) {
            $redirect->with('print_referral_id', $createdReferralId);
        }

        return $redirect;
    }

    // 3. Show the Doctor's Workspace (View Consultation)
    public function show($consultation)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

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
        $existingDiagnoses = $this->diagnosisRecordsQuery()
            ->where('diagnosis_records.consultation_id', $consultation->id)
            ->select(
                'diagnosis_records.*',
                'diagnosis_lookup.diagnosis_code as diagnosis_code',
                'diagnosis_lookup.diagnosis_name as diagnosis_name',
                DB::raw('(diagnosis_records.diagnosis_id IS NULL) as is_custom')
            )
            ->get();

        $existingPrescriptions = $this->prescriptionsQuery()
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

    public function acknowledgeIntake($consultation)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $worker = $this->currentWorker();
        if (strtolower((string) $worker->role) !== 'nurse') {
            abort(403, 'Only nurses can acknowledge intake.');
        }

        if ($consultation->status !== ConsultationStatus::NurseReview->value) {
            return redirect()->back()->withErrors([
                'intake' => 'This consultation is not awaiting nurse validation.',
            ]);
        }

        DB::table('consultations')->where('id', $consultation->id)->update([
            'status' => ConsultationStatus::DoctorReview->value,
            'nurse_validated_at' => now(),
            'nurse_validated_by' => $worker->id,
            'updated_at' => now(),
        ]);

        return redirect()->route('consultations.show', $consultation->id)
            ->with('success', 'Intake acknowledged. Patient is now in the doctor queue.');
    }

    public function cancelIntake($consultation)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $worker = $this->currentWorker();
        if (strtolower((string) $worker->role) !== 'nurse') {
            abort(403, 'Only nurses can cancel intake requests.');
        }

        if ($consultation->status !== ConsultationStatus::NurseReview->value) {
            return redirect()->back()->withErrors([
                'intake' => 'Only consultations awaiting nurse validation can be canceled.',
            ]);
        }

        DB::transaction(function () use ($consultation) {
            DB::table('vitals')->where('consultation_id', $consultation->id)->delete();
            DB::table('outward_referrals')->where('consultation_id', $consultation->id)->delete();
            DB::table('consultations')->where('id', $consultation->id)->delete();
        });

        return redirect()->route('dashboard')
            ->with('success', 'Intake canceled successfully.');
    }

    public function printHandout($consultation)
    {
        return view('consultations.handout', $this->resolveHandoutData($consultation));
    }

    public function downloadHandoutPdf($consultation)
    {
        $data = $this->resolveHandoutData($consultation);
        $filename = 'iClinicSys-Handout-C'.str_pad((string) $data['consultation']->id, 4, '0', STR_PAD_LEFT).'.pdf';

        return Pdf::view('consultations.handout-pdf', $data)
            ->format(Format::A4)
            ->margins(6, 6, 6, 6)
            ->inline($filename);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveHandoutData($consultation): array
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        if (! auth()->user()->canPrintHandout()) {
            abort(403, 'You do not have permission to print consultation handouts.');
        }

        if (! in_array($consultation->status, ConsultationStatus::terminalValues(), true)) {
            abort(403, 'Print handout is available only for completed consultations.');
        }

        $outwardReferral = DB::table('outward_referrals')
            ->where('consultation_id', $consultation->id)
            ->first();

        $patient = DB::table('patients')
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->leftJoin('zones', 'households.zone_id', '=', 'zones.id')
            ->where('patients.id', $consultation->patient_id)
            ->select(
                'patients.*',
                'households.contact_number as household_contact_number',
                'households.id as household_record_id',
                'zones.zone_number'
            )
            ->first();

        $vitals = DB::table('vitals')
            ->where('consultation_id', $consultation->id)
            ->orderByDesc('id')
            ->first();

        $diagnoses = $this->diagnosisRecordsQuery()
            ->where('diagnosis_records.consultation_id', $consultation->id)
            ->select(
                'diagnosis_lookup.diagnosis_name as diagnosis_name',
                'diagnosis_lookup.diagnosis_code as diagnosis_code',
                'diagnosis_records.remarks'
            )
            ->orderBy('diagnosis_records.id')
            ->get();

        $prescriptions = $this->prescriptionsQuery()
            ->where('prescriptions.consultation_id', $consultation->id)
            ->select(
                'medicines_lookup.name as medicine_name',
                'prescriptions.dosage',
                'prescriptions.frequency',
                'prescriptions.duration',
                'prescriptions.quantity'
            )
            ->orderBy('prescriptions.id')
            ->get();

        $age = $patient ? Carbon::parse($patient->date_of_birth)->age : null;
        $zoneLabel = $patient?->zone_number ? 'Zone '.$patient->zone_number : null;

        $consultationAt = Carbon::parse($consultation->updated_at ?? $consultation->created_at);
        $attendingProvider = trim(($consultation->worker_first_name ?? '').' '.($consultation->worker_last_name ?? '')) ?: null;

        return [
            'consultation' => $consultation,
            'outwardReferral' => $outwardReferral,
            'patient' => $patient,
            'diagnoses' => $diagnoses,
            'prescriptions' => $prescriptions,
            'vitals' => $vitals,
            'age' => $age,
            'zoneLabel' => $zoneLabel,
            'consultationAt' => $consultationAt,
            'attendingProvider' => $attendingProvider,
        ];
    }

    public function retakeVitals(VitalsRequest $request, $consultation)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validated();
        $worker = $this->currentWorker();

        if ($redirect = $this->guardClinicalReviewStage($consultation)) {
            return $redirect;
        }

        $vitalsPayload = VitalsService::fromInput($validated) + [
            'consultation_id' => $consultation->id,
            'phase' => 'clinical',
            'captured_by' => $worker->id,
            'notes' => $validated['notes'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('vitals')->insert($vitalsPayload);

        DB::table('consultations')
            ->where('id', $consultation->id)
            ->update(['status' => ConsultationStatus::InProgress->value, 'updated_at' => now()]);

        return redirect()->route('consultations.show', $consultation->id)
            ->with('success', 'Clinical vitals saved as a new version.');
    }

    public function updateVitalVersion(VitalsRequest $request, $consultation, $vitalId)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validated();

        $vital = DB::table('vitals')
            ->where('id', $vitalId)
            ->where('consultation_id', $consultation->id)
            ->first();

        if (! $vital) {
            abort(404, 'Vitals version not found for this consultation.');
        }

        $updatePayload = VitalsService::fromInput($validated) + [
            'notes' => $validated['notes'] ?? null,
            'updated_at' => now(),
        ];

        DB::table('vitals')
            ->where('id', $vitalId)
            ->where('consultation_id', $consultation->id)
            ->update($updatePayload);

        return redirect()->route('consultations.show', $consultation->id)
            ->with('success', 'Vitals version updated successfully.');
    }

    public function deleteVitalVersion($consultation, $vitalId)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $versions = DB::table('vitals')
            ->where('consultation_id', $consultation->id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($versions->count() <= 1) {
            return redirect()->route('consultations.show', $consultation->id)
                ->withErrors(['vitals' => 'Cannot delete the only vitals version.']);
        }

        $vital = $versions->firstWhere('id', (int) $vitalId);
        if (! $vital) {
            abort(404, 'Vitals version not found for this consultation.');
        }

        if (($vital->phase ?? null) === 'triage') {
            return redirect()->route('consultations.show', $consultation->id)
                ->withErrors(['vitals' => 'Triage baseline vitals cannot be deleted.']);
        }

        DB::table('vitals')
            ->where('id', $vitalId)
            ->where('consultation_id', $consultation->id)
            ->delete();

        return redirect()->route('consultations.show', $consultation->id)
            ->with('success', 'Vitals version deleted successfully.');
    }

    // 4. Save a Diagnosis (Doctor's Action)
    public function addDiagnosis(AddDiagnosisRequest $request, $consultation)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $worker = $this->currentWorker();
        if (strtolower((string) $worker->role) !== 'doctor') {
            abort(403, 'Only doctors can add diagnoses.');
        }

        $validated = $request->validated();

        if ($redirect = $this->guardClinicalReviewStage($consultation)) {
            return $redirect;
        }

        DB::table('diagnosis_records')->insert([
            'consultation_id' => $consultation->id,
            'diagnosis_id' => $validated['diagnosis_id'],
            'remarks' => $validated['remarks'] ?? null,
            'diagnosed_by' => $worker->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($this->maybeAutoCompleteConsultation((int) $consultation->id)) {
            return redirect()->back()->with('success', 'Diagnosis added. Consultation marked as completed.');
        }

        DB::table('consultations')->where('id', $consultation->id)->update([
            'status' => ConsultationStatus::InProgress->value,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Diagnosis added successfully!');
    }

    public function referralContext($consultation)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $patient = DB::table('patients')->find($consultation->patient_id);
        if (! $patient) {
            abort(404, 'Patient not found');
        }

        $latestVitals = Vitals::query()
            ->where('consultation_id', $consultation->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $patientName = trim((string) (($patient->last_name ?? '').', '.($patient->first_name ?? '')));
        $patientMetaParts = [];
        if (! empty($patient->date_of_birth)) {
            $patientMetaParts[] = Carbon::parse($patient->date_of_birth)->age.' y/o';
        }
        if (! empty($patient->sex)) {
            $patientMetaParts[] = ucfirst($patient->sex);
        }
        $patientMeta = implode(' · ', $patientMetaParts);

        $vitalsSummary = $latestVitals?->summary ?? '';

        return response()->json([
            'patient_name' => $patientName ?: '—',
            'patient_meta' => $patientMeta ?: '—',
            'vitals_summary' => $vitalsSummary ?: '—',
        ]);
    }

    public function refer(ReferralRequest $request, $consultation)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $worker = $this->currentWorker();
        if (! in_array(strtolower((string) $worker->role), ['doctor', 'nurse'], true)) {
            abort(403, 'Only Nurse and Doctor roles can refer patients.');
        }

        $validated = $request->validated();

        if (! in_array($consultation->status, [ConsultationStatus::NurseReview->value, ConsultationStatus::DoctorReview->value, ConsultationStatus::InProgress->value], true)) {
            return redirect()->back()->withErrors(['referral' => 'Referral can only be submitted while the consultation is active or pending validation.']);
        }

        DB::transaction(function () use ($validated, $consultation) {
            $existingReferral = DB::table('outward_referrals')
                ->where('consultation_id', $consultation->id)
                ->first();

            $referralPayload = [
                'consultation_id' => $consultation->id,
                'destination_facility' => $validated['referred_to'],
                'pertinent_history' => $validated['pertinent_history'],
                'actions_taken' => $validated['actions_taken'] ?? null,
                'specific_details' => ReferralService::specificDetails($validated['referral_reasons'] ?? [], $validated['referral_reason_details'] ?? null),
                'status' => 'pending',
                'updated_at' => now(),
            ];

            if ($existingReferral) {
                DB::table('outward_referrals')
                    ->where('id', $existingReferral->id)
                    ->update($referralPayload);
            } else {
                $referralPayload['created_at'] = now();
                DB::table('outward_referrals')->insert($referralPayload);
            }

            DB::table('consultations')
                ->where('id', $consultation->id)
                ->update([
                    'status' => ConsultationStatus::Referred->value,
                    'updated_at' => now(),
                ]);
        });

        return redirect()->route('consultations.show', $consultation->id)
            ->with('success', 'Referral request submitted and consultation marked as referred.');
    }

    public function finalizeConsultation(FinalizeConsultationRequest $request, $consultation)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validated();

        if ($redirect = $this->guardClinicalReviewStage($consultation)) {
            return $redirect;
        }

        $diagnosisCount = DB::table('diagnosis_records')
            ->where('consultation_id', $consultation->id)
            ->count();

        if ($diagnosisCount < 1) {
            return redirect()->route('consultations.show', $consultation->id)
                ->withErrors(['diagnosis' => 'Add at least one diagnosis before finalizing consultation.']);
        }

        $worker = $this->currentWorker();

        $updates = [
            'status' => ConsultationStatus::Completed->value,
            'updated_at' => now(),
        ];

        $requestedReferral = (bool) ($validated['refer_to_higher_facility'] ?? false);
        if ($requestedReferral) {
            $currentWorkerRole = strtolower((string) $worker->role);

            if (! in_array($currentWorkerRole, ['doctor', 'nurse'], true)) {
                return redirect()->back()->withErrors([
                    'refer_to_higher_facility' => 'Only Doctor or Nurse roles can trigger external referral.',
                ])->withInput();
            }

            $updates['status'] = ConsultationStatus::Referred->value;

            $existingReferral = DB::table('outward_referrals')
                ->where('consultation_id', $consultation->id)
                ->first();

            $referralPayload = [
                'consultation_id' => $consultation->id,
                'destination_facility' => $validated['referred_to'] ?? null,
                'pertinent_history' => $validated['pertinent_history'] ?? $existingReferral->pertinent_history ?? null,
                'actions_taken' => $validated['actions_taken'] ?? $existingReferral->actions_taken ?? null,
                'specific_details' => ReferralService::specificDetails($validated['referral_reasons'] ?? [], $validated['referral_reason_details'] ?? null) ?? $existingReferral->specific_details ?? null,
                'updated_at' => now(),
            ];

            if ($existingReferral) {
                DB::table('outward_referrals')
                    ->where('id', $existingReferral->id)
                    ->update($referralPayload);
            } else {
                $referralPayload['status'] = 'pending';
                $referralPayload['created_at'] = now();
                DB::table('outward_referrals')->insert($referralPayload);
            }
        }

        DB::table('consultations')
            ->where('id', $consultation->id)
            ->update($updates);

        return redirect()->route('consultations.show', $consultation->id)
            ->with('success', $requestedReferral
                ? 'Consultation finalized and marked as referred.'
                : 'Consultation finalized successfully.');
    }

    // 5. Save a Prescription
    public function addPrescription(AddPrescriptionRequest $request, $consultation)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $worker = $this->currentWorker();
        if (strtolower((string) $worker->role) !== 'doctor') {
            abort(403, 'Only doctors can add prescriptions.');
        }

        $validated = $request->validated();

        if ($redirect = $this->guardClinicalReviewStage($consultation)) {
            return $redirect;
        }

        DB::table('prescriptions')->insert([
            'consultation_id' => $consultation->id,
            'medicine_id' => $validated['medicine_id'],
            'dosage' => $validated['dosage'],
            'frequency' => $validated['frequency'] ?? null,
            'duration' => $validated['duration'] ?? null,
            'quantity' => $validated['quantity'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($this->maybeAutoCompleteConsultation((int) $consultation->id)) {
            return redirect()->back()->with('success', 'Prescription added. Consultation marked as completed.');
        }

        DB::table('consultations')->where('id', $consultation->id)->update([
            'status' => ConsultationStatus::InProgress->value,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Prescription added successfully.');
    }

    // Edit Consultation (Quick edit for notes/treatments)
    public function edit($consultation)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        // Get patient info
        $patient = DB::table('patients')->find($consultation->patient_id);

        // Get diagnoses
        $diagnoses = $this->diagnosisRecordsQuery()
            ->where('diagnosis_records.consultation_id', $consultation->id)
            ->select(
                'diagnosis_records.id',
                'diagnosis_lookup.diagnosis_name as diagnosis_name',
                'diagnosis_records.remarks'
            )
            ->get();

        // Get prescriptions
        $prescriptions = $this->prescriptionsQuery()
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

    public function update(UpdateConsultationRequest $request, $consultation)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        // Update notes if provided
        if ($request->has('notes')) {
            DB::table('consultations')
                ->where('id', $consultation->id)
                ->update(['notes' => $request->input('notes'), 'updated_at' => now()]);
        }

        return redirect()->route('consultations.show', $consultation->id)->with('success', 'Consultation updated successfully.');
    }

    public function deleteDiagnosis(Request $request, $consultation, $diagnosisId)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $diagnosis = DB::table('diagnosis_records')
            ->where('id', $diagnosisId)
            ->where('consultation_id', $consultation->id)
            ->first();

        if (! $diagnosis) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Diagnosis not found'], 404);
            }
            abort(404, 'Diagnosis not found');
        }

        DB::table('diagnosis_records')->where('id', $diagnosisId)->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Diagnosis deleted successfully']);
        }

        return redirect()->route('consultations.edit', $consultation->id)->with('success', 'Diagnosis deleted successfully.');
    }

    public function deletePrescription(Request $request, $consultation, $prescriptionId)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $prescription = DB::table('prescriptions')
            ->where('id', $prescriptionId)
            ->where('consultation_id', $consultation->id)
            ->first();

        if (! $prescription) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Prescription not found'], 404);
            }
            abort(404, 'Prescription not found');
        }

        DB::table('prescriptions')->where('id', $prescriptionId)->delete();

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

    private function guardClinicalReviewStage(object $consultation): ?RedirectResponse
    {
        if (in_array($consultation->status, [ConsultationStatus::DoctorReview->value, ConsultationStatus::InProgress->value], true)) {
            return null;
        }

        $message = match ($consultation->status) {
            ConsultationStatus::NurseReview->value => 'Nurse intake validation must be completed before clinical review.',
            ConsultationStatus::Triage->value => 'Triage intake must be completed before clinical review.',
            default => 'This consultation is not open for clinical review.',
        };

        return redirect()->back()->withErrors(['consultation' => $message]);
    }

    private function maybeAutoCompleteConsultation(int $consultationId): bool
    {
        $consultation = DB::table('consultations')->where('id', $consultationId)->first();
        if (! $consultation || in_array($consultation->status, ConsultationStatus::terminalValues(), true)) {
            return false;
        }

        $hasDiagnosis = DB::table('diagnosis_records')->where('consultation_id', $consultationId)->exists();
        $hasPrescription = DB::table('prescriptions')->where('consultation_id', $consultationId)->exists();

        if (! $hasDiagnosis || ! $hasPrescription) {
            return false;
        }

        DB::table('consultations')->where('id', $consultationId)->update([
            'status' => ConsultationStatus::Completed->value,
            'updated_at' => now(),
        ]);

        return true;
    }

    private function diagnosisRecordsQuery()
    {
        return DB::table('diagnosis_records')
            ->leftJoin('diagnosis_lookup', 'diagnosis_records.diagnosis_id', '=', 'diagnosis_lookup.id');
    }

    private function prescriptionsQuery()
    {
        return DB::table('prescriptions')
            ->leftJoin('medicines_lookup', 'prescriptions.medicine_id', '=', 'medicines_lookup.id');
    }
}
