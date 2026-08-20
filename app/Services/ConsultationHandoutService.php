<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\FamilyPlanningClient;
use App\Models\Immunization;
use App\Models\PostnatalRecord;
use App\Models\Pregnancy;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Assembles the data shared by the print handout and handout PDF.
 */
final class ConsultationHandoutService
{
    /**
     * @return array<string, mixed>
     */
    public static function data(Consultation $consultation): array
    {
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

        // philhealth_no is encrypted at rest; this query-builder read bypasses the
        // model cast, so decrypt it manually for the handout (defensive for legacy rows).
        if ($patient && filled($patient->philhealth_no ?? null)) {
            try {
                $patient->philhealth_no = decrypt($patient->philhealth_no);
            } catch (\Throwable) {
                // Leave as-is if the value is not a valid encrypted payload.
            }
        }

        $vitals = DB::table('vitals')
            ->where('consultation_id', $consultation->id)
            ->orderByDesc('id')
            ->first();

        $diagnoses = ConsultationQueryService::diagnosisRecordsQuery()
            ->where('diagnosis_records.consultation_id', $consultation->id)
            ->select(
                'diagnosis_lookup.diagnosis_name as diagnosis_name',
                'diagnosis_lookup.diagnosis_code as diagnosis_code',
                'diagnosis_records.remarks'
            )
            ->orderBy('diagnosis_records.id')
            ->get();

        $prescriptions = ConsultationQueryService::prescriptionsQuery()
            ->where('prescriptions.consultation_id', $consultation->id)
            ->select(
                'medicines_lookup.name as medicine_name',
                'prescriptions.dosage',
                'prescriptions.route',
                'prescriptions.frequency',
                'prescriptions.duration',
                'prescriptions.quantity',
                'prescriptions.instructions'
            )
            ->orderBy('prescriptions.id')
            ->get();

        $age = $patient ? Carbon::parse($patient->date_of_birth)->age : null;
        $zoneLabel = $patient?->zone_number ? 'Zone '.$patient->zone_number : null;

        $consultationAt = Carbon::parse($consultation->updated_at ?? $consultation->created_at);
        $attendingProvider = trim(
            ($consultation->attending_doctor_first_name ?? $consultation->worker_first_name ?? '').' '
            .($consultation->attending_doctor_last_name ?? $consultation->worker_last_name ?? '')
        ) ?: null;

        $pregnancy = Pregnancy::with('visits')->where('patient_id', $consultation->patient_id)->latest('id')->first();
        $prenatalVisits = $pregnancy ? $pregnancy->visits : collect();
        $postnatalRecord = PostnatalRecord::where('patient_id', $consultation->patient_id)->latest('id')->first();
        $fpClient = FamilyPlanningClient::where('patient_id', $consultation->patient_id)->where('is_active', true)->latest('id')->first();
        $immunizations = Immunization::with('vaccine')->where('patient_id', $consultation->patient_id)->orderBy('date_given')->get();

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
            'pregnancy' => $pregnancy,
            'prenatalVisits' => $prenatalVisits,
            'postnatalRecord' => $postnatalRecord,
            'fpClient' => $fpClient,
            'immunizations' => $immunizations,
        ];
    }
}
