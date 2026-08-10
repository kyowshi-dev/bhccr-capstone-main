<?php

namespace App\Services;

use App\Enums\ConsultationStatus;
use App\Models\Consultation;
use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-side queries for consultations (listing, aggregates, shared builders).
 */
final class ConsultationQueryService
{
    /**
     * Paginate the consultation history using the index filters.
     *
     * @param  array{sort?: string, query?: string, date_from?: string, date_to?: string}  $filters
     */
    public static function paginateIndex(array $filters = [], ?User $user = null): LengthAwarePaginator
    {
        $query = Consultation::query()
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->join('health_workers', 'consultations.worker_id', '=', 'health_workers.id')
            ->leftJoin('health_workers as attending_doctor', 'consultations.attending_doctor_id', '=', 'attending_doctor.id')
            ->select(
                'consultations.*',
                'patients.first_name as patient_first_name',
                'patients.last_name as patient_last_name',
                'health_workers.first_name as worker_first_name',
                'health_workers.last_name as worker_last_name',
                'attending_doctor.first_name as attending_doctor_first_name',
                'attending_doctor.last_name as attending_doctor_last_name'
            );

        if ($user !== null) {
            $user->scopeAccessibleConsultations($query);
        }

        switch ($filters['sort'] ?? 'newest') {
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

        if (! empty($filters['query'])) {
            $q = (string) $filters['query'];
            $query->where(function ($qb) use ($q) {
                $qb->where('patients.first_name', 'like', '%'.$q.'%')
                    ->orWhere('patients.last_name', 'like', '%'.$q.'%')
                    ->orWhereRaw(dbConcat(['patients.last_name', 'patients.first_name'], ', ').' LIKE ?', ['%'.$q.'%']);
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

        if (! empty($filters['date_from'])) {
            $parsed = Carbon::createFromFormat('d/m/Y', trim((string) $filters['date_from']));
            if ($parsed) {
                $query->where('consultations.created_at', '>=', $parsed->copy()->startOfDay());
            }
        }
        if (! empty($filters['date_to'])) {
            $parsed = Carbon::createFromFormat('d/m/Y', trim((string) $filters['date_to']));
            if ($parsed) {
                $query->where('consultations.created_at', '<=', $parsed->copy()->endOfDay());
            }
        }

        return $query->paginate(15)->withQueryString();
    }

    /**
     * Map consultation IDs to formatted diagnosis lines for the index list.
     *
     * @param  list<int>  $consultationIds
     * @return array<int, list<string>>
     */
    public static function diagnosesByConsultation(array $consultationIds): array
    {
        $grouped = [];

        if (empty($consultationIds)) {
            return $grouped;
        }

        $rows = self::diagnosisRecordsQuery()
            ->whereIn('diagnosis_records.consultation_id', $consultationIds)
            ->select(
                'diagnosis_records.consultation_id',
                'diagnosis_lookup.diagnosis_name as diagnosis_name',
                'diagnosis_records.remarks'
            )
            ->orderBy('diagnosis_records.id')
            ->get();

        foreach ($rows as $row) {
            $grouped[$row->consultation_id][] = trim($row->diagnosis_name.($row->remarks ? ' - '.$row->remarks : ''));
        }

        return $grouped;
    }

    /**
     * Map consultation IDs to formatted prescription strings for the index list.
     *
     * @param  list<int>  $consultationIds
     * @return array<int, list<string>>
     */
    public static function treatmentsByConsultation(array $consultationIds): array
    {
        $grouped = [];

        if (empty($consultationIds)) {
            return $grouped;
        }

        $rows = self::prescriptionsQuery()
            ->whereIn('prescriptions.consultation_id', $consultationIds)
            ->select(
                'prescriptions.consultation_id',
                'medicines_lookup.name as medicine_name',
                'prescriptions.dosage',
                'prescriptions.duration'
            )
            ->get();

        foreach ($rows as $row) {
            $grouped[$row->consultation_id][] = $row->medicine_name.($row->dosage ? ' '.$row->dosage : '').($row->duration ? ', '.$row->duration : '');
        }

        return $grouped;
    }

    /**
     * Summary counts shown on the consultations index.
     *
     * @return array{total: int, thisWeek: int, completed: int}
     */
    public static function indexStats(?User $user = null): array
    {
        $total = Consultation::query();
        $thisWeek = Consultation::query()->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        $completed = Consultation::query()->where('status', ConsultationStatus::Completed->value);

        if ($user !== null) {
            $user->scopeAccessibleConsultations($total);
            $user->scopeAccessibleConsultations($thisWeek);
            $user->scopeAccessibleConsultations($completed);
        }

        return [
            'total' => (clone $total)->count(),
            'thisWeek' => (clone $thisWeek)->count(),
            'completed' => (clone $completed)->count(),
        ];
    }

    public static function diagnosisRecordsQuery(): Builder
    {
        return DB::table('diagnosis_records')
            ->leftJoin('diagnosis_lookup', 'diagnosis_records.diagnosis_id', '=', 'diagnosis_lookup.id');
    }

    public static function prescriptionsQuery(): Builder
    {
        return DB::table('prescriptions')
            ->leftJoin('medicines_lookup', 'prescriptions.medicine_id', '=', 'medicines_lookup.id');
    }

    public static function previousVitalsFor(Patient $patient): ?\stdClass
    {
        return DB::table('vitals')
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
    }

    public static function diagnosesForEdit(Consultation $consultation): Collection
    {
        return self::diagnosisRecordsQuery()
            ->where('diagnosis_records.consultation_id', $consultation->id)
            ->select(
                'diagnosis_records.id',
                'diagnosis_lookup.diagnosis_name as diagnosis_name',
                'diagnosis_records.remarks'
            )
            ->get();
    }

    public static function prescriptionsForEdit(Consultation $consultation): Collection
    {
        return self::prescriptionsQuery()
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
    }

    /**
     * Fetch and atomically mark the next unnotified DoctorReview consultation
     * for the live-requests polling endpoint.
     *
     * @return array<string, mixed>|null
     */
    public static function nextUnnotifiedDoctorReview(): ?array
    {
        $consultation = DB::table('consultations')
            ->where('status', ConsultationStatus::DoctorReview->value)
            ->whereNull('notified_at')
            ->orderByDesc('created_at')
            ->first();

        if (! $consultation) {
            return null;
        }

        $patient = DB::table('patients')->where('id', $consultation->patient_id)->first();
        $worker = DB::table('health_workers')->where('id', $consultation->worker_id)->first();

        if (! $patient || ! $worker) {
            return null;
        }

        DB::table('consultations')
            ->where('id', $consultation->id)
            ->update(['notified_at' => now()]);

        return [
            'id' => $consultation->id,
            'open_url' => route('consultations.show', ['consultation' => $consultation->id]),
            'clinic_name' => 'Santa Ana Health Center',
            'worker_name' => trim(($worker->first_name ?? '').' '.($worker->last_name ?? '')),
            'patient_name' => trim(($patient->first_name ?? '').' '.($patient->last_name ?? '')),
            'patient_age' => $patient->date_of_birth ? Carbon::parse($patient->date_of_birth)->age : null,
            'patient_gender' => $patient->sex ?? '',
            'chief_complaint' => $consultation->complaint_text ?? $consultation->chief_complaint ?? 'No reason provided',
        ];
    }
}
