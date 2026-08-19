<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\HealthWorker;
use App\Models\Patient;
use App\Models\Vitals;
use Illuminate\Support\Facades\DB;

final class ConsultationWorkspaceService
{
    public static function data(Consultation $consultation, ?HealthWorker $healthWorker): array
    {
        $patient = Patient::query()->find($consultation->patient_id);

        $allVitals = self::allVitals($consultation);
        $triageVitals = $allVitals->firstWhere('phase', 'triage') ?? $allVitals->first();
        $latestVitals = $allVitals->last();
        $vitals = $latestVitals ?? (object) [
            'bp_systolic' => null,
            'bp_diastolic' => null,
            'temperature_c' => null,
            'weight_kg' => null,
            'height_cm' => null,
            'phase' => 'triage',
        ];

        return [
            'consultation' => $consultation,
            'patient' => $patient,
            'vitals' => $vitals,
            'triageVitals' => $triageVitals,
            'latestVitals' => $latestVitals,
            'allVitals' => $allVitals,
            'diagnoses' => self::diagnosesFor($consultation),
            'prescriptions' => self::prescriptionsFor($consultation),
            'diagnosisOptions' => DB::table('diagnosis_lookup')->orderBy('diagnosis_name')->get(),
            'medicineOptions' => DB::table('medicines_lookup')->whereNull('deleted_at')->orderBy('name')->get(),
            'linkedPrenatalVisits' => self::linkedPrenatalVisits($consultation),
            'linkedPostnatal' => self::linkedPostnatal($consultation),
            'linkedFpVisits' => self::linkedFpVisits($consultation),
            'canReferExternally' => $healthWorker?->isClinical() ?? false,
            'canAcknowledgeIntake' => $healthWorker?->isNurse() ?? false,
            'canAddDiagnosis' => $healthWorker?->isDoctor() ?? false,
            'canAddPrescription' => $healthWorker?->isDoctor() ?? false,
        ];
    }

    private static function allVitals(Consultation $consultation): mixed
    {
        return Vitals::query()
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
    }

    private static function diagnosesFor(Consultation $consultation): mixed
    {
        return ConsultationQueryService::diagnosisRecordsQuery()
            ->where('diagnosis_records.consultation_id', $consultation->id)
            ->select(
                'diagnosis_records.*',
                'diagnosis_lookup.diagnosis_code as diagnosis_code',
                'diagnosis_lookup.diagnosis_name as diagnosis_name',
                DB::raw('(diagnosis_records.diagnosis_id IS NULL) as is_custom')
            )
            ->get();
    }

    private static function prescriptionsFor(Consultation $consultation): mixed
    {
        return ConsultationQueryService::prescriptionsQuery()
            ->where('prescriptions.consultation_id', $consultation->id)
            ->select(
                'prescriptions.*',
                'medicines_lookup.name as medicine_name',
                DB::raw('(prescriptions.medicine_id IS NULL) as is_custom')
            )
            ->get();
    }

    private static function linkedPrenatalVisits(Consultation $consultation): mixed
    {
        return DB::table('prenatal_visits')
            ->where('consultation_id', $consultation->id)
            ->orderBy('visit_date')
            ->get();
    }

    private static function linkedPostnatal(Consultation $consultation): mixed
    {
        return DB::table('postnatal_records')
            ->where('consultation_id', $consultation->id)
            ->latest('id')
            ->first();
    }

    private static function linkedFpVisits(Consultation $consultation): mixed
    {
        return DB::table('family_planning_visits')
            ->where('consultation_id', $consultation->id)
            ->orderBy('visit_date')
            ->get();
    }
}
