<?php

namespace App\Http\Controllers;

use App\Models\Consultation;
use App\Models\Patient;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\LaravelPdf\Enums\Format;
use Spatie\LaravelPdf\Facades\Pdf;

class ConsultationController extends Controller
{
    private ?bool $supportsVersionedVitals = null;

    public function index(Request $request)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $query = DB::table('consultations')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->join('health_workers', 'consultations.worker_id', '=', 'health_workers.id')
            ->select(
                'consultations.id',
                'consultations.patient_id',
                'consultations.status',
                'consultations.created_at',
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
                        ->where(function ($dx) use ($q) {
                            $dx->where('diagnosis_lookup.diagnosis_name', 'like', '%'.$q.'%')
                                ->orWhere('diagnosis_records.custom_diagnosis_name', 'like', '%'.$q.'%');
                        });
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
                    DB::raw('COALESCE(diagnosis_lookup.diagnosis_name, diagnosis_records.custom_diagnosis_name) as diagnosis_name'),
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
                    DB::raw('COALESCE(medicines_lookup.medicine_name, prescriptions.custom_medicine_name) as medicine_name'),
                    'prescriptions.dosage',
                    'prescriptions.duration'
                )
                ->get();
            foreach ($prescriptionRows as $row) {
                $treatmentByConsultation[$row->consultation_id][] = $row->medicine_name.($row->dosage ? ' '.$row->dosage : '').($row->duration ? ', '.$row->duration : '');
            }
        }

        $totalConsultations = DB::table('consultations')->count();
        $thisWeekCount = DB::table('consultations')->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->count();
        $completedCount = DB::table('consultations')->where('status', 'completed')->count();

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
            ->where('status', 'pending_doctor')
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
                'open_url' => route('consultations.show', ['id' => $consultation->id]),
                'clinic_name' => 'Santa Ana Health Center',
                'worker_name' => trim(($worker->first_name ?? '').' '.($worker->last_name ?? '')),
                'patient_name' => trim(($patient->first_name ?? '').' '.($patient->last_name ?? '')),
                'patient_age' => $patient->date_of_birth ? Carbon::parse($patient->date_of_birth)->age : null,
                'patient_gender' => $patient->gender ?? '',
                'chief_complaint' => $consultation->complaint_text ?? $consultation->chief_complaint ?? 'No reason provided',
            ],
        ]);
    }

    // 1. Show the Admission Form (Triage) — modal partial via AJAX; redirect for direct navigation
    public function create(Request $request, $patientId)
    {
        $patient = Patient::find($patientId);

        if (! $patient) {
            abort(404, 'Patient not found');
        }

        $patient->age = Carbon::parse($patient->date_of_birth)->age;

        $previousVitals = DB::table('vitals')
            ->join('consultations', 'vitals.consultation_id', '=', 'consultations.id')
            ->where('consultations.patient_id', $patientId)
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
            ->back(fallback: route('patients.show', $patientId))
            ->with('open_consultation_for', $patientId);
    }

    // 2. Save the Data (Triage Save)
    public function store(Request $request, $patientId)
    {
        $validated = $request->validate([
            'mode_of_transaction' => ['required', 'string', 'max:255'],
            'referred_from' => ['nullable', 'string', 'max:255'],
            'nature_of_visit' => ['required', 'string', 'max:255'],
            'purpose_of_visit' => ['required', 'string', 'max:255'],
            'chief_complaint' => ['nullable', 'string', 'max:1000'],
            'bp_systolic' => ['required', 'numeric', 'min:0', 'max:300'],
            'bp_diastolic' => ['required', 'numeric', 'min:0', 'max:200'],
            'temperature' => ['required', 'numeric', 'min:30', 'max:45'],
            'weight' => ['required', 'numeric', 'min:0', 'max:500'],
            'height' => ['required', 'numeric', 'min:0', 'max:300'],
            'refer_to_higher_facility' => ['nullable', 'boolean'],
            'referred_to' => ['required_if:refer_to_higher_facility,1', 'nullable', 'string', 'max:255'],
            'referral_reasons' => ['nullable', 'array'],
            'referral_reasons.*' => ['string'],
            'referral_reason_details' => ['nullable', 'string', 'max:1000'],
            'pertinent_history' => ['required_if:refer_to_higher_facility,1', 'nullable', 'string'],
            'actions_taken' => ['nullable', 'string'],
        ], [
            'temperature.required' => 'Temperature is required.',
            'temperature.min' => 'Temperature must be at least 30°C.',
            'temperature.max' => 'Temperature must not exceed 45°C.',
        ]);

        $workerId = DB::table('health_workers')
            ->where('user_id', Auth::id())
            ->value('id');

        if ($workerId === null) {
            abort(403, 'No health worker profile is linked to this user.');
        }

        $consultationId = null;
        $createdReferralId = null;

        DB::transaction(function () use ($validated, $patientId, $workerId, &$consultationId, &$createdReferralId) {
            $consultationId = DB::table('consultations')->insertGetId([
                'patient_id' => $patientId,
                'worker_id' => $workerId,
                'status' => 'pending_validation',
                'nature_of_visit' => $validated['nature_of_visit'],
                'mode_of_transaction' => $validated['mode_of_transaction'],
                'referred_from' => $validated['referred_from'] ?? null,
                'chief_complaint_id' => null,
                'complaint_text' => $validated['chief_complaint'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (! empty($validated['refer_to_higher_facility'])) {
                $reasonLabels = [
                    'specialized_evaluation' => 'Need for specialized medical evaluation / physician',
                    'lack_diagnostics' => 'Lack of diagnostic equipment / laboratory tests',
                    'lack_medicines' => 'Lack of available medicines / vaccines',
                    'emergency_trauma' => 'Emergency / trauma stabilization required',
                ];

                $reasons = array_filter(array_map(function ($reason) use ($reasonLabels) {
                    return $reasonLabels[$reason] ?? $reason;
                }, $validated['referral_reasons'] ?? []));

                $details = trim($validated['referral_reason_details'] ?? '');
                $reasonText = $reasons ? 'Reasons: '.implode(', ', $reasons) : '';
                $specificDetails = trim($reasonText.($details ? "\n\n".$details : '')) ?: null;

                $createdReferralId = DB::table('outward_referrals')->insertGetId([
                    'consultation_id' => $consultationId,
                    'destination_facility' => $validated['referred_to'],
                    'pertinent_history' => $validated['pertinent_history'],
                    'actions_taken' => $validated['actions_taken'] ?? null,
                    'specific_details' => $specificDetails,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $vitalsPayload = [
                'consultation_id' => $consultationId,
                'bp_systolic' => $validated['bp_systolic'] ?? null,
                'bp_diastolic' => $validated['bp_diastolic'] ?? null,
                'weight_kg' => $validated['weight'] ?? null,
                'height_cm' => $validated['height'] ?? null,
                'temperature_c' => $validated['temperature'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($this->vitalsSupportVersioning()) {
                $vitalsPayload['phase'] = 'triage';
                $vitalsPayload['captured_by'] = $workerId;
            }

            DB::table('vitals')->insert($vitalsPayload);
        });

        $redirect = redirect()->route('patients.show', $patientId)
            ->with('success', 'Consultation started. Patient is awaiting nurse intake validation.');

        if ($createdReferralId) {
            $redirect->with('print_referral_id', $createdReferralId);
        }

        return $redirect;
    }

    // 3. Show the Doctor's Workspace (View Consultation)
    public function show($id)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $consultation = DB::table('consultations')
            ->leftJoin('health_workers', 'consultations.worker_id', '=', 'health_workers.id')
            ->where('consultations.id', $id)
            ->select(
                'consultations.*',
                'health_workers.first_name as worker_first_name',
                'health_workers.last_name as worker_last_name'
            )
            ->first();

        if (! $consultation) {
            abort(404, 'Resource not found');
        }

        $patient = DB::table('patients')->find($consultation->patient_id);

        $vitalsQuery = DB::table('vitals')
            ->where('vitals.consultation_id', $id)
            ->orderBy('vitals.created_at')
            ->orderBy('vitals.id');

        if ($this->vitalsSupportVersioning()) {
            $vitalsQuery
                ->leftJoin('health_workers', 'vitals.captured_by', '=', 'health_workers.id')
                ->select(
                    'vitals.*',
                    'health_workers.first_name as captured_by_first_name',
                    'health_workers.last_name as captured_by_last_name',
                    'health_workers.role as captured_by_role'
                );
        } else {
            $vitalsQuery->select('vitals.*');
        }

        $allVitals = $vitalsQuery->get();

        $triageVitals = $this->vitalsSupportVersioning()
            ? ($allVitals->firstWhere('phase', 'triage') ?? $allVitals->first())
            : $allVitals->first();
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

        $currentUserRole = DB::table('health_workers')
            ->where('user_id', Auth::id())
            ->value('role');

        $canReferExternally = in_array(strtolower((string) $currentUserRole), ['doctor', 'nurse'], true);
        $canAcknowledgeIntake = strtolower((string) $currentUserRole) === 'nurse';
        $canAddDiagnosis = strtolower((string) $currentUserRole) === 'doctor';
        $canAddPrescription = strtolower((string) $currentUserRole) === 'doctor';

        // 2. Fetch Existing Records (History)
        $existingDiagnoses = $this->diagnosisRecordsQuery()
            ->where('diagnosis_records.consultation_id', $id)
            ->select(
                'diagnosis_records.*',
                DB::raw('COALESCE(diagnosis_lookup.diagnosis_code, diagnosis_records.custom_diagnosis_code) as diagnosis_code'),
                DB::raw('COALESCE(diagnosis_lookup.diagnosis_name, diagnosis_records.custom_diagnosis_name) as diagnosis_name'),
                DB::raw('(diagnosis_records.diagnosis_id IS NULL) as is_custom')
            )
            ->get();

        $existingPrescriptions = $this->prescriptionsQuery()
            ->where('prescriptions.consultation_id', $id)
            ->select(
                'prescriptions.*',
                DB::raw('COALESCE(medicines_lookup.medicine_name, prescriptions.custom_medicine_name) as medicine_name'),
                DB::raw('(prescriptions.medicine_id IS NULL) as is_custom')
            )
            ->get();

        // 3. NEW: Fetch Dropdown Options (The "Menu" for the Doctor)
        $diagnosisOptions = DB::table('diagnosis_lookup')->orderBy('diagnosis_name')->get();
        $medicineOptions = DB::table('medicines_lookup')->orderBy('medicine_name')->get();

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

    public function acknowledgeIntake($id)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $worker = DB::table('health_workers')->where('user_id', Auth::id())->first();
        if ($worker === null || strtolower((string) $worker->role) !== 'nurse') {
            abort(403, 'Only nurses can acknowledge intake.');
        }

        $consultation = DB::table('consultations')->where('id', $id)->first();
        if (! $consultation) {
            abort(404, 'Consultation not found');
        }

        if ($consultation->status !== 'pending_validation') {
            return redirect()->back()->withErrors([
                'intake' => 'This consultation is not awaiting nurse validation.',
            ]);
        }

        $updates = [
            'status' => 'pending_doctor',
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('consultations', 'nurse_validated_at')) {
            $updates['nurse_validated_at'] = now();
            $updates['nurse_validated_by'] = $worker->id;
        }

        DB::table('consultations')->where('id', $id)->update($updates);

        return redirect()->route('consultations.show', $id)
            ->with('success', 'Intake acknowledged. Patient is now in the doctor queue.');
    }

    public function cancelIntake($id)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $worker = DB::table('health_workers')->where('user_id', Auth::id())->first();
        if ($worker === null || strtolower((string) $worker->role) !== 'nurse') {
            abort(403, 'Only nurses can cancel intake requests.');
        }

        $consultation = DB::table('consultations')->where('id', $id)->first();
        if (! $consultation) {
            abort(404, 'Consultation not found');
        }

        if ($consultation->status !== 'pending_validation') {
            return redirect()->back()->withErrors([
                'intake' => 'Only consultations awaiting nurse validation can be canceled.',
            ]);
        }

        DB::transaction(function () use ($id) {
            DB::table('vitals')->where('consultation_id', $id)->delete();
            DB::table('outward_referrals')->where('consultation_id', $id)->delete();
            DB::table('consultations')->where('id', $id)->delete();
        });

        return redirect()->route('dashboard')
            ->with('success', 'Intake canceled successfully.');
    }

    public function printHandout($id)
    {
        return view('consultations.handout', $this->resolveHandoutData($id));
    }

    public function downloadHandoutPdf($id)
    {
        $data = $this->resolveHandoutData($id);
        $filename = 'iClinicSys-Handout-C'.str_pad((string) $data['consultation']->id, 4, '0', STR_PAD_LEFT).'.pdf';

        return Pdf::view('consultations.handout-pdf', $data)
            ->format(Format::A4)
            ->margins(6, 6, 6, 6)
            ->inline($filename);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveHandoutData(int|string $id): array
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        if (! auth()->user()->canPrintHandout()) {
            abort(403, 'You do not have permission to print consultation handouts.');
        }

        $consultation = DB::table('consultations')
            ->leftJoin('health_workers', 'consultations.worker_id', '=', 'health_workers.id')
            ->where('consultations.id', $id)
            ->select(
                'consultations.*',
                'health_workers.first_name as worker_first_name',
                'health_workers.last_name as worker_last_name'
            )
            ->first();

        if (! $consultation) {
            abort(404, 'Consultation not found');
        }

        $outwardReferral = DB::table('outward_referrals')
            ->where('consultation_id', $id)
            ->first();

        if (! in_array($consultation->status, ['completed', 'referred'], true)) {
            abort(403, 'Print handout is available only for completed consultations.');
        }

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
            ->where('consultation_id', $id)
            ->orderByDesc('id')
            ->first();

        $diagnoses = $this->diagnosisRecordsQuery()
            ->where('diagnosis_records.consultation_id', $id)
            ->select(
                DB::raw('COALESCE(diagnosis_lookup.diagnosis_name, diagnosis_records.custom_diagnosis_name) as diagnosis_name'),
                DB::raw('COALESCE(diagnosis_lookup.diagnosis_code, diagnosis_records.custom_diagnosis_code) as diagnosis_code'),
                'diagnosis_records.remarks'
            )
            ->orderBy('diagnosis_records.id')
            ->get();

        $prescriptions = $this->prescriptionsQuery()
            ->where('prescriptions.consultation_id', $id)
            ->select(
                DB::raw('COALESCE(medicines_lookup.medicine_name, prescriptions.custom_medicine_name) as medicine_name'),
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

    public function retakeVitals(Request $request, $id)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'bp_systolic' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'bp_diastolic' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'notes' => ['nullable', 'string', 'max:500']
        ]);

        $consultation = DB::table('consultations')->where('id', $id)->first();
        if (! $consultation) {
            abort(404, 'Consultation not found');
        }

        if ($redirect = $this->guardClinicalReviewStage($consultation)) {
            return $redirect;
        }

        $workerId = DB::table('health_workers')
            ->where('user_id', Auth::id())
            ->value('id');

        if ($workerId === null) {
            abort(403, 'No health worker profile is linked to this user.');
        }

        $vitalsPayload = [
            'consultation_id' => $id,
            'bp_systolic' => $validated['bp_systolic'] ?? null,
            'bp_diastolic' => $validated['bp_diastolic'] ?? null,
            'weight_kg' => $validated['weight'] ?? null,
            'height_cm' => $validated['height'] ?? null,
            'temperature_c' => $validated['temperature'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($this->vitalsSupportVersioning()) {
            $vitalsPayload['phase'] = 'clinical';
            $vitalsPayload['captured_by'] = $workerId;
            $vitalsPayload['notes'] = $validated['notes'] ?? null;
        }

        DB::table('vitals')->insert($vitalsPayload);

        DB::table('consultations')
            ->where('id', $id)
            ->update(['status' => 'in_progress', 'updated_at' => now()]);

        return redirect()->route('consultations.show', $id)
            ->with('success', 'Clinical vitals saved as a new version.');
    }

    public function updateVitalVersion(Request $request, $consultationId, $vitalId)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'bp_systolic' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'bp_diastolic' => ['nullable', 'numeric', 'min:0', 'max:200'],
            'temperature' => ['nullable', 'numeric', 'min:30', 'max:45'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $vital = DB::table('vitals')
            ->where('id', $vitalId)
            ->where('consultation_id', $consultationId)
            ->first();

        if (! $vital) {
            abort(404, 'Vitals version not found for this consultation.');
        }

        $updatePayload = [
            'bp_systolic' => $validated['bp_systolic'] ?? null,
            'bp_diastolic' => $validated['bp_diastolic'] ?? null,
            'weight_kg' => $validated['weight'] ?? null,
            'height_cm' => $validated['height'] ?? null,
            'temperature_c' => $validated['temperature'] ?? null,
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('vitals', 'notes')) {
            $updatePayload['notes'] = $validated['notes'] ?? null;
        }

        DB::table('vitals')
            ->where('id', $vitalId)
            ->where('consultation_id', $consultationId)
            ->update($updatePayload);

        return redirect()->route('consultations.show', $consultationId)
            ->with('success', 'Vitals version updated successfully.');
    }

    public function deleteVitalVersion($consultationId, $vitalId)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $versions = DB::table('vitals')
            ->where('consultation_id', $consultationId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        if ($versions->count() <= 1) {
            return redirect()->route('consultations.show', $consultationId)
                ->withErrors(['vitals' => 'Cannot delete the only vitals version.']);
        }

        $vital = $versions->firstWhere('id', (int) $vitalId);
        if (! $vital) {
            abort(404, 'Vitals version not found for this consultation.');
        }

        if ($this->vitalsSupportVersioning() && ($vital->phase ?? null) === 'triage') {
            return redirect()->route('consultations.show', $consultationId)
                ->withErrors(['vitals' => 'Triage baseline vitals cannot be deleted.']);
        }

        DB::table('vitals')
            ->where('id', $vitalId)
            ->where('consultation_id', $consultationId)
            ->delete();

        return redirect()->route('consultations.show', $consultationId)
            ->with('success', 'Vitals version deleted successfully.');
    }

    // 4. Save a Diagnosis (Doctor's Action)
    public function addDiagnosis(Request $request, $id)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $currentUserRole = DB::table('health_workers')
            ->where('user_id', Auth::id())
            ->value('role');

        if (strtolower((string) $currentUserRole) !== 'doctor') {
            abort(403, 'Only doctors can add diagnoses.');
        }

        $validated = $request->validate([
            'diagnosis_id' => ['required', 'integer', 'exists:diagnosis_lookup,id'],
            'remarks' => ['nullable', 'string'],
        ]);

        $workerId = DB::table('health_workers')
            ->where('user_id', Auth::id())
            ->value('id');

        if ($workerId === null) {
            abort(403, 'No health worker profile is linked to this user.');
        }

        $consultation = DB::table('consultations')->where('id', $id)->first();
        if (! $consultation) {
            abort(404, 'Consultation not found');
        }

        if ($redirect = $this->guardClinicalReviewStage($consultation)) {
            return $redirect;
        }

        DB::table('diagnosis_records')->insert([
            'consultation_id' => $id,
            'diagnosis_id' => $validated['diagnosis_id'],
            'remarks' => $validated['remarks'] ?? null,
            'diagnosed_by' => $workerId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($this->maybeAutoCompleteConsultation((int) $id)) {
            return redirect()->back()->with('success', 'Diagnosis added. Consultation marked as completed.');
        }

        DB::table('consultations')->where('id', $id)->update([
            'status' => 'in_progress',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Diagnosis added successfully!');
    }

    public function referralContext($id)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $consultation = DB::table('consultations')->where('id', $id)->first();
        if (! $consultation) {
            abort(404, 'Consultation not found');
        }

        $patient = DB::table('patients')->find($consultation->patient_id);
        if (! $patient) {
            abort(404, 'Patient not found');
        }

        $latestVitals = DB::table('vitals')
            ->where('consultation_id', $id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        $patientName = trim((string) (($patient->last_name ?? '') . ', ' . ($patient->first_name ?? '')));
        $patientMetaParts = [];
        if (! empty($patient->date_of_birth)) {
            $patientMetaParts[] = Carbon::parse($patient->date_of_birth)->age . ' y/o';
        }
        if (! empty($patient->gender)) {
            $patientMetaParts[] = ucfirst($patient->gender);
        }
        $patientMeta = implode(' · ', $patientMetaParts);

        $vitalsSummaryParts = [];
        if ($latestVitals) {
            if ($latestVitals->bp_systolic !== null || $latestVitals->bp_diastolic !== null) {
                $vitalsSummaryParts[] = 'BP ' . ($latestVitals->bp_systolic ?? '—') . '/' . ($latestVitals->bp_diastolic ?? '—') . ' mmHg';
            }
            if ($latestVitals->temperature_c !== null) {
                $vitalsSummaryParts[] = 'Temp ' . $latestVitals->temperature_c . '°C';
            }
            if ($latestVitals->weight_kg !== null) {
                $vitalsSummaryParts[] = 'Weight ' . $latestVitals->weight_kg . ' kg';
            }
            if ($latestVitals->height_cm !== null) {
                $vitalsSummaryParts[] = 'Height ' . $latestVitals->height_cm . ' cm';
            }
        }
        $vitalsSummary = implode(' · ', $vitalsSummaryParts);

        return response()->json([
            'patient_name' => $patientName ?: '—',
            'patient_meta' => $patientMeta ?: '—',
            'vitals_summary' => $vitalsSummary ?: '—',
        ]);
    }

    public function refer(Request $request, $id)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $currentUserRole = DB::table('health_workers')
            ->where('user_id', Auth::id())
            ->value('role');

        if (! in_array(strtolower((string) $currentUserRole), ['doctor', 'nurse'], true)) {
            abort(403, 'Only Nurse and Doctor roles can refer patients.');
        }

        $validated = $request->validate([
            'referred_to' => ['required', 'string', 'max:255'],
            'referral_reasons' => ['nullable', 'array'],
            'referral_reasons.*' => ['string'],
            'referral_reason_details' => ['nullable', 'string', 'max:1000'],
            'pertinent_history' => ['required', 'nullable', 'string'],
            'actions_taken' => ['nullable', 'string'],
        ]);

        $consultation = DB::table('consultations')->where('id', $id)->first();
        if (! $consultation) {
            abort(404, 'Consultation not found');
        }

        if (! in_array($consultation->status, ['pending_validation', 'pending_doctor', 'in_progress'], true)) {
            return redirect()->back()->withErrors(['referral' => 'Referral can only be submitted while the consultation is active or pending validation.']);
        }

        DB::transaction(function () use ($validated, $id) {
            $existingReferral = DB::table('outward_referrals')
                ->where('consultation_id', $id)
                ->first();

            $reasonLabels = [
                'specialized_evaluation' => 'Need for specialized medical evaluation / physician',
                'lack_diagnostics' => 'Lack of diagnostic equipment / laboratory tests',
                'lack_medicines' => 'Lack of available medicines / vaccines',
                'emergency_trauma' => 'Emergency / trauma stabilization required',
            ];

            $reasons = array_filter(array_map(function ($reason) use ($reasonLabels) {
                return $reasonLabels[$reason] ?? $reason;
            }, $validated['referral_reasons'] ?? []));

            $details = trim($validated['referral_reason_details'] ?? '');
            $reasonText = $reasons ? 'Reasons: '.implode(', ', $reasons) : '';
            $specificDetails = trim($reasonText.($details ? "\n\n".$details : '')) ?: null;

            $referralPayload = [
                'consultation_id' => $id,
                'destination_facility' => $validated['referred_to'],
                'pertinent_history' => $validated['pertinent_history'],
                'actions_taken' => $validated['actions_taken'] ?? null,
                'specific_details' => $specificDetails,
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
                ->where('id', $id)
                ->update([
                    'status' => 'referred',
                    'updated_at' => now(),
                ]);
        });

        return redirect()->route('consultations.show', $id)
            ->with('success', 'Referral request submitted and consultation marked as referred.');
    }

    public function finalizeConsultation(Request $request, $id)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'refer_to_higher_facility' => ['nullable', 'boolean'],
            'referred_to' => ['required_if:refer_to_higher_facility,1', 'nullable', 'string', 'max:255'],
            'referral_reasons' => ['nullable', 'array'],
            'referral_reasons.*' => ['string'],
            'referral_reason_details' => ['nullable', 'string', 'max:1000'],
            'pertinent_history' => ['required_if:refer_to_higher_facility,1', 'nullable', 'string'],
            'actions_taken' => ['nullable', 'string'],
        ]);

        $consultation = DB::table('consultations')->where('id', $id)->first();
        if (! $consultation) {
            abort(404, 'Consultation not found');
        }

        if ($redirect = $this->guardClinicalReviewStage($consultation)) {
            return $redirect;
        }

        $diagnosisCount = DB::table('diagnosis_records')
            ->where('consultation_id', $id)
            ->count();

        if ($diagnosisCount < 1) {
            return redirect()->route('consultations.show', $id)
                ->withErrors(['diagnosis' => 'Add at least one diagnosis before finalizing consultation.']);
        }

        $workerId = DB::table('health_workers')
            ->where('user_id', Auth::id())
            ->value('id');

        if ($workerId === null) {
            abort(403, 'No health worker profile is linked to this user.');
        }

        $updates = [
            'status' => 'completed',
            'updated_at' => now(),
        ];

        $requestedReferral = (bool) ($validated['refer_to_higher_facility'] ?? false);
        if ($requestedReferral) {
            $currentWorkerRole = strtolower((string) DB::table('health_workers')
                ->where('id', $workerId)
                ->value('role'));

            if (! in_array($currentWorkerRole, ['doctor', 'nurse'], true)) {
                return redirect()->back()->withErrors([
                    'refer_to_higher_facility' => 'Only Doctor or Nurse roles can trigger external referral.',
                ])->withInput();
            }

            $updates['status'] = 'referred';

            $existingReferral = DB::table('outward_referrals')
                ->where('consultation_id', $id)
                ->first();

            $reasonLabels = [
                'specialized_evaluation' => 'Need for specialized medical evaluation / physician',
                'lack_diagnostics' => 'Lack of diagnostic equipment / laboratory tests',
                'lack_medicines' => 'Lack of available medicines / vaccines',
                'emergency_trauma' => 'Emergency / trauma stabilization required',
            ];

            $reasons = array_filter(array_map(function ($reason) use ($reasonLabels) {
                return $reasonLabels[$reason] ?? $reason;
            }, $validated['referral_reasons'] ?? []));

            $details = trim($validated['referral_reason_details'] ?? '');
            $reasonText = $reasons ? 'Reasons: '.implode(', ', $reasons) : '';
            $specificDetails = trim($reasonText.($details ? "\n\n".$details : '')) ?: null;

            $referralPayload = [
                'consultation_id' => $id,
                'destination_facility' => $validated['referred_to'] ?? null,
                'pertinent_history' => $validated['pertinent_history'] ?? $existingReferral->pertinent_history ?? null,
                'actions_taken' => $validated['actions_taken'] ?? $existingReferral->actions_taken ?? null,
                'specific_details' => $specificDetails ?? $existingReferral->specific_details ?? null,
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
            ->where('id', $id)
            ->update($updates);

        return redirect()->route('consultations.show', $id)
            ->with('success', $requestedReferral
                ? 'Consultation finalized and marked as referred.'
                : 'Consultation finalized successfully.');
    }

    // 5. Save a Prescription
    public function addPrescription(Request $request, $id)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $currentUserRole = DB::table('health_workers')
            ->where('user_id', Auth::id())
            ->value('role');

        if (strtolower((string) $currentUserRole) !== 'doctor') {
            abort(403, 'Only doctors can add prescriptions.');
        }

        $validated = $request->validate([
            'medicine_id' => ['required', 'integer', 'exists:medicines_lookup,id'],
            'dosage' => ['required', 'string', 'max:255'],
            'frequency' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $consultation = DB::table('consultations')->where('id', $id)->first();
        if (! $consultation) {
            abort(404, 'Consultation not found');
        }

        if ($redirect = $this->guardClinicalReviewStage($consultation)) {
            return $redirect;
        }

        DB::table('prescriptions')->insert([
            'consultation_id' => $id,
            'medicine_id' => $validated['medicine_id'],
            'dosage' => $validated['dosage'],
            'frequency' => $validated['frequency'] ?? null,
            'duration' => $validated['duration'] ?? null,
            'quantity' => $validated['quantity'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($this->maybeAutoCompleteConsultation((int) $id)) {
            return redirect()->back()->with('success', 'Prescription added. Consultation marked as completed.');
        }

        DB::table('consultations')->where('id', $id)->update([
            'status' => 'in_progress',
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Prescription added successfully.');
    }

    // Edit Consultation (Quick edit for notes/treatments)
    public function edit($id)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $consultation = DB::table('consultations')
            ->leftJoin('health_workers', 'consultations.worker_id', '=', 'health_workers.id')
            ->where('consultations.id', $id)
            ->select(
                'consultations.*',
                'health_workers.first_name as worker_first_name',
                'health_workers.last_name as worker_last_name'
            )
            ->first();

        if (! $consultation) {
            abort(404, 'Consultation not found');
        }

        // Get patient info
        $patient = DB::table('patients')->find($consultation->patient_id);

        // Get diagnoses
        $diagnoses = $this->diagnosisRecordsQuery()
            ->where('diagnosis_records.consultation_id', $id)
            ->select(
                'diagnosis_records.id',
                DB::raw('COALESCE(diagnosis_lookup.diagnosis_name, diagnosis_records.custom_diagnosis_name) as diagnosis_name'),
                'diagnosis_records.remarks'
            )
            ->get();

        // Get prescriptions
        $prescriptions = $this->prescriptionsQuery()
            ->where('prescriptions.consultation_id', $id)
            ->select(
                'prescriptions.id',
                DB::raw('COALESCE(medicines_lookup.medicine_name, prescriptions.custom_medicine_name) as medicine_name'),
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

    public function update(Request $request, $id)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $consultation = DB::table('consultations')->find($id);
        if (! $consultation) {
            abort(404, 'Consultation not found');
        }

        // Update notes if provided
        if ($request->has('notes')) {
            DB::table('consultations')
                ->where('id', $id)
                ->update(['notes' => $request->input('notes'), 'updated_at' => now()]);
        }

        return redirect()->route('consultations.show', $id)->with('success', 'Consultation updated successfully.');
    }

    public function deleteDiagnosis(Request $request, $consultationId, $diagnosisId)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $diagnosis = DB::table('diagnosis_records')
            ->where('id', $diagnosisId)
            ->where('consultation_id', $consultationId)
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

        return redirect()->route('consultations.edit', $consultationId)->with('success', 'Diagnosis deleted successfully.');
    }

    public function deletePrescription(Request $request, $consultationId, $prescriptionId)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $prescription = DB::table('prescriptions')
            ->where('id', $prescriptionId)
            ->where('consultation_id', $consultationId)
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

        return redirect()->route('consultations.edit', $consultationId)->with('success', 'Prescription deleted successfully.');
    }

    private function guardClinicalReviewStage(object $consultation): ?RedirectResponse
    {
        if (in_array($consultation->status, ['pending_doctor', 'in_progress'], true)) {
            return null;
        }

        $message = match ($consultation->status) {
            'pending_validation' => 'Nurse intake validation must be completed before clinical review.',
            'triage' => 'Triage intake must be completed before clinical review.',
            default => 'This consultation is not open for clinical review.',
        };

        return redirect()->back()->withErrors(['consultation' => $message]);
    }

    private function maybeAutoCompleteConsultation(int $consultationId): bool
    {
        $consultation = DB::table('consultations')->where('id', $consultationId)->first();
        if (! $consultation || in_array($consultation->status, ['completed', 'referred'], true)) {
            return false;
        }

        $hasDiagnosis = DB::table('diagnosis_records')->where('consultation_id', $consultationId)->exists();
        $hasPrescription = DB::table('prescriptions')->where('consultation_id', $consultationId)->exists();

        if (! $hasDiagnosis || ! $hasPrescription) {
            return false;
        }

        DB::table('consultations')->where('id', $consultationId)->update([
            'status' => 'completed',
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

    private function vitalsSupportVersioning(): bool
    {
        if ($this->supportsVersionedVitals !== null) {
            return $this->supportsVersionedVitals;
        }

        $this->supportsVersionedVitals = Schema::hasColumn('vitals', 'phase')
            && Schema::hasColumn('vitals', 'captured_by');

        return $this->supportsVersionedVitals;
    }
}
