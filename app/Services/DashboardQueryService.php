<?php

namespace App\Services;

use App\Enums\ConsultationStatus;
use App\Helpers\PatientCode;
use App\Models\Patient;
use App\Models\User;
use App\Models\Vaccine;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-side queries for the role-specific dashboards.
 */
final class DashboardQueryService
{
    public static function totalPatients(?User $user = null): int
    {
        $query = DB::table('patients');

        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('household_id', $user->accessibleHouseholdIds());
        }

        return $query->count();
    }

    public static function consultationsToday(Carbon $today, ?User $user = null): int
    {
        $query = DB::table('consultations')
            ->whereDate('created_at', $today);

        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('patient_id', $user->accessiblePatientIds());
        }

        return $query->count();
    }

    public static function activeConsultations(?User $user = null): int
    {
        $query = DB::table('consultations')
            ->whereIn('status', ConsultationStatus::activeValues());

        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('patient_id', $user->accessiblePatientIds());
        }

        return $query->count();
    }

    public static function followUpsToday(Carbon $today): int
    {
        return DB::table('consultations')
            ->whereDate('created_at', $today)
            ->where('nature_of_visit', 'Follow-up')
            ->count();
    }

    public static function overdueImmunizations(Carbon $today): int
    {
        $patients = Patient::query()
            ->whereHas('household')
            ->where('is_immunization_enrolled', true)
            ->whereHas('immunizationRecords', fn ($q) => $q->where('no_show', false))
            ->with('immunizationRecords')
            ->get();

        $vaccines = Vaccine::whereIn('category', ['Child', 'Adult', 'Both'])
            ->with('schedules')
            ->get();

        $service = app(ChildImmunizationService::class);
        $count = 0;

        foreach ($patients as $patient) {
            foreach ($vaccines as $vaccine) {
                $status = $service->statusFor($patient, $vaccine);

                if (in_array($status, ['overdue', 'out_of_window'], true)) {
                    $count++;
                    break;
                }
            }
        }

        return $count;
    }

    public static function recentActivity(int $limit = 5, ?User $user = null): Collection
    {
        $query = DB::table('audit_logs')
            ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
            ->select('audit_logs.*', 'users.username')
            ->orderByDesc('audit_logs.created_at')
            ->limit($limit);

        // Zone-scoped workers only see their own activity on the dashboard.
        if ($user !== null && $user->isZoneScoped()) {
            $query->where('audit_logs.user_id', $user->id);
        }

        return $query->get();
    }

    /**
     * Terminal consultations with their diagnosis summaries, used by the
     * "results ready" handout panel on each dashboard.
     *
     * @param  array{query?: string, from?: string, to?: string}  $inputs
     * @return array{resultsReady: Collection, resultsReadyCount: int, resultsFilters: array{query: string, from: string, to: string}}
     */
    public static function resultsReady(array $inputs, int $limit = 15, bool $defaultToToday = false, ?User $user = null): array
    {
        $resultsQuery = DB::table('consultations')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->whereIn('consultations.status', ConsultationStatus::terminalValues())
            ->select(
                'consultations.id',
                'consultations.updated_at',
                'patients.first_name',
                'patients.last_name',
                'patients.id as patient_id'
            )
            ->orderByDesc('consultations.updated_at');

        if ($user !== null && $user->isZoneScoped()) {
            $resultsQuery->whereIn('patients.household_id', $user->accessibleHouseholdIds());
        }

        if (! empty($inputs['query'] ?? null)) {
            $q = (string) $inputs['query'];
            $resultsQuery->where(function ($qb) use ($q) {
                $qb->where('patients.first_name', 'like', '%'.$q.'%')
                    ->orWhere('patients.last_name', 'like', '%'.$q.'%');
                if (is_numeric($q)) {
                    $qb->orWhere('patients.id', (int) $q);
                }
            });
        }

        $from = (string) ($inputs['from'] ?? ($defaultToToday ? Carbon::today()->toDateString() : ''));
        $to = (string) ($inputs['to'] ?? ($defaultToToday ? Carbon::today()->toDateString() : ''));

        if ($from !== '') {
            $resultsQuery->whereDate('consultations.updated_at', '>=', $from);
        }
        if ($to !== '') {
            $resultsQuery->whereDate('consultations.updated_at', '<=', $to);
        }

        $resultsReady = $resultsQuery->limit($limit)->get();
        $resultIds = $resultsReady->pluck('id')->all();
        $diagnosisSummaryByConsultation = [];

        if (! empty($resultIds)) {
            $dxRows = DB::table('diagnosis_records')
                ->join('diagnosis_lookup', 'diagnosis_records.diagnosis_id', '=', 'diagnosis_lookup.id')
                ->whereIn('diagnosis_records.consultation_id', $resultIds)
                ->select('diagnosis_records.consultation_id', 'diagnosis_lookup.diagnosis_name')
                ->orderBy('diagnosis_records.id')
                ->get();

            foreach ($dxRows as $dxRow) {
                $diagnosisSummaryByConsultation[$dxRow->consultation_id][] = $dxRow->diagnosis_name;
            }
        }

        $resultsReady = $resultsReady->map(function ($row) use ($diagnosisSummaryByConsultation) {
            $names = $diagnosisSummaryByConsultation[$row->id] ?? [];
            $row->diagnosis_summary = $names ? implode(', ', $names) : null;

            return $row;
        });

        return [
            'resultsReady' => $resultsReady,
            'resultsReadyCount' => (clone $resultsQuery)->count(),
            'resultsFilters' => [
                'query' => (string) ($inputs['query'] ?? ''),
                'from' => $from,
                'to' => $to,
            ],
        ];
    }

    // ── B H W   d a s h b o a r d ──

    public static function pendingQueue(?User $user = null, int $limit = 5): Collection
    {
        $query = DB::table('consultations')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->whereIn('consultations.status', ConsultationStatus::activeValues());

        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('patients.household_id', $user->accessibleHouseholdIds());
        }

        return $query
            ->orderBy('consultations.created_at')
            ->limit($limit)
            ->select(
                'patients.first_name',
                'patients.last_name',
                'patients.id as patient_id',
                'consultations.status'
            )
            ->get()
            ->map(fn ($row) => (object) [
                'name' => trim($row->first_name.' '.$row->last_name),
                'identifier' => PatientCode::format((int) $row->patient_id).' · '.ConsultationStatus::labelOf($row->status),
            ]);
    }

    public static function recentPatients(?User $user = null, int $limit = 3): Collection
    {
        $query = DB::table('patients')
            ->select('id', 'first_name', 'last_name')
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('household_id', $user->accessibleHouseholdIds());
        }

        return $query->get()
            ->map(fn ($row) => (object) [
                'id' => $row->id,
                'name' => trim($row->first_name.' '.$row->last_name),
                'identifier' => PatientCode::format((int) $row->id),
            ]);
    }

    // ── N u r s e   d a s h b o a r d ──

    /**
     * @return array{pendingValidationCount: int, intakePipelineCount: int}
     */
    public static function nurseCounts(): array
    {
        return [
            'pendingValidationCount' => DB::table('consultations')
                ->where('status', ConsultationStatus::NurseReview->value)
                ->count(),
            'intakePipelineCount' => DB::table('consultations')
                ->whereIn('status', [ConsultationStatus::Triage->value, ConsultationStatus::NurseReview->value])
                ->count(),
        ];
    }

    public static function validationQueue(int $limit = 8): Collection
    {
        return DB::table('consultations')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->where('consultations.status', ConsultationStatus::NurseReview->value)
            ->orderBy('consultations.created_at')
            ->limit($limit)
            ->select(
                'consultations.id',
                'consultations.created_at',
                'consultations.complaint_text',
                'patients.first_name',
                'patients.last_name'
            )
            ->get();
    }

    // ── D o c t o r   d a s h b o a r d ──

    /**
     * @return array{pendingDoctorCount: int, completedConsultationsToday: int, consultationsToday: int}
     */
    public static function doctorCounts(Carbon $today): array
    {
        $doctorReviewStatuses = [ConsultationStatus::DoctorReview->value, ConsultationStatus::InProgress->value];

        $pendingDoctorCount = DB::table('consultations')
            ->whereIn('status', $doctorReviewStatuses)
            ->count();

        $completedConsultationsToday = DB::table('consultations')
            ->whereDate('updated_at', $today)
            ->where('status', ConsultationStatus::Completed->value)
            ->count();

        return [
            'pendingDoctorCount' => $pendingDoctorCount,
            'completedConsultationsToday' => $completedConsultationsToday,
            'consultationsToday' => $pendingDoctorCount + $completedConsultationsToday,
        ];
    }

    /**
     * @return list<array{id: int, patient_name: string, status: string, time: string, complaint_text: string|null}>
     */
    public static function doctorQueue(int $limit = 8): array
    {
        return DB::table('consultations')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->whereIn('consultations.status', [ConsultationStatus::DoctorReview->value, ConsultationStatus::InProgress->value])
            ->orderBy('consultations.created_at')
            ->limit($limit)
            ->select(
                'consultations.id',
                'consultations.status',
                'consultations.created_at',
                'consultations.complaint_text',
                'patients.first_name',
                'patients.last_name'
            )
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'patient_name' => trim("{$row->first_name} {$row->last_name}"),
                    'status' => ConsultationStatus::labelOf($row->status, ucfirst(str_replace('_', ' ', (string) $row->status))),
                    'time' => Carbon::parse($row->created_at)->diffForHumans(),
                    'complaint_text' => $row->complaint_text,
                ];
            })
            ->all();
    }

    // ── A d m i n   d a s h b o a r d ──

    public static function patientVolumeByDay(Carbon $start, Carbon $end): Collection
    {
        return DB::table('consultations')
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereBetween('created_at', [$start, $end])
            ->groupByRaw('DATE(created_at)')
            ->orderBy('day')
            ->get();
    }

    public static function topIllnesses(Carbon $start, Carbon $end, int $limit = 6): Collection
    {
        return DB::table('diagnosis_records')
            ->leftJoin('diagnosis_lookup', 'diagnosis_records.diagnosis_id', '=', 'diagnosis_lookup.id')
            ->leftJoin('consultations', 'diagnosis_records.consultation_id', '=', 'consultations.id')
            ->whereBetween('consultations.created_at', [$start, $end])
            ->selectRaw("COALESCE(diagnosis_lookup.diagnosis_name, 'Unspecified') as name, COUNT(*) as total")
            ->groupBy('name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array{doctorsOnDuty: int, onDutyStaff: list<array{name: string, role: string, initials: string}>}
     */
    public static function onDuty(int $limit = 5): array
    {
        $activeWorkers = DB::table('health_workers')
            ->join('users', 'health_workers.user_id', '=', 'users.id')
            ->where('users.is_active', true);

        $doctorsOnDuty = (clone $activeWorkers)->count();

        $onDutyStaff = (clone $activeWorkers)
            ->select('health_workers.first_name', 'health_workers.last_name', 'health_workers.role')
            ->orderBy('health_workers.last_name')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $initials = mb_strtoupper(mb_substr($row->first_name, 0, 1).mb_substr($row->last_name, 0, 1));

                return [
                    'name' => trim($row->first_name.' '.$row->last_name),
                    'role' => (string) $row->role,
                    'initials' => $initials,
                ];
            })
            ->all();

        return compact('doctorsOnDuty', 'onDutyStaff');
    }
}
