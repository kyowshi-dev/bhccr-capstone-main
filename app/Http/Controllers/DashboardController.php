<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Asantibanez\LivewireCharts\Models\LineChartModel;
use Asantibanez\LivewireCharts\Models\PieChartModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $healthWorker = DB::table('health_workers')->where('user_id', $user->id)->first();
        $today = Carbon::today();

        if ($healthWorker && strtolower($healthWorker->role) === 'bhw') {
            $totalPatients = DB::table('patients')->count();

            $consultationsToday = DB::table('consultations')
                ->whereDate('created_at', $today)
                ->count();

            $pendingConsultations = DB::table('consultations')
                ->whereIn('status', ['triage', 'nurse_review', 'doctor_review', 'in_progress'])
                ->count();

            $pendingQueue = DB::table('consultations')
                ->join('patients', 'consultations.patient_id', '=', 'patients.id')
                ->whereIn('consultations.status', ['triage', 'nurse_review', 'doctor_review', 'in_progress'])
                ->orderBy('consultations.created_at')
                ->limit(5)
                ->select(
                    'patients.first_name',
                    'patients.last_name',
                    'patients.id as patient_id',
                    'consultations.status'
                )
                ->get()
                ->map(function ($row) {
                    return (object) [
                        'name' => trim($row->first_name.' '.$row->last_name),
                        'identifier' => 'PT'.str_pad((string) $row->patient_id, 3, '0', STR_PAD_LEFT).' · '.str_replace('_', ' ', (string) $row->status),
                    ];
                });

            $recentActivity = DB::table('audit_logs')
                ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
                ->select('audit_logs.*', 'users.username')
                ->orderByDesc('audit_logs.created_at')
                ->limit(5)
                ->get();

            $recentPatients = DB::table('patients')
                ->select('id', 'first_name', 'last_name')
                ->orderByDesc('created_at')
                ->limit(3)
                ->get()
                ->map(function ($row) {
                    return (object) [
                        'id' => $row->id,
                        'name' => trim($row->first_name.' '.$row->last_name),
                        'identifier' => 'PT'.str_pad((string) $row->id, 3, '0', STR_PAD_LEFT),
                    ];
                });

            $handoutData = $user->canViewDashboardHandouts('bhw')
                ? $this->loadResultsReady($request)
                : ['resultsReady' => collect(), 'resultsReadyCount' => 0, 'resultsFilters' => $this->emptyResultsFilters()];

            return view('dashboard_bhw', [
                'totalPatients' => $totalPatients,
                'consultationsToday' => $consultationsToday,
                'pendingConsultations' => $pendingConsultations,
                'pendingQueue' => $pendingQueue,
                'recentPatients' => $recentPatients,
                'queueUpdatedAt' => now()->format('M j, Y g:i A'),
                'showResultsReady' => $user->canViewDashboardHandouts('bhw'),
                ...$handoutData,
            ]);
        }

        if ($healthWorker && strtolower($healthWorker->role) === 'nurse') {
            return $this->nurseDashboard($request, $user, $today);
        }

        if ($healthWorker && strtolower($healthWorker->role) === 'doctor') {
            return $this->doctorDashboard($request, $user, $today);
        }

        $totalPatients = DB::table('patients')->count();

        $pendingAppointments = DB::table('consultations')
            ->whereIn('status', ['triage', 'nurse_review', 'doctor_review', 'in_progress'])
            ->count();

        $overdueImmunizations = DB::table('immunization_records')
            ->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '<', $today)
            ->distinct('patient_id')
            ->count('patient_id');

        $followUpConsultationsToday = DB::table('consultations')
            ->whereDate('created_at', $today)
            ->where('nature_of_visit', 'Follow-up')
            ->count();

        $volumeStart = $today->copy()->subDays(6)->startOfDay();
        $volumeEnd = $today->copy()->endOfDay();

        $patientVolumeRows = DB::table('consultations')
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereBetween('created_at', [$volumeStart, $volumeEnd])
            ->groupByRaw('DATE(created_at)')
            ->orderBy('day')
            ->get();

        $volumeByDay = [];
        foreach ($patientVolumeRows as $row) {
            $volumeByDay[Carbon::parse($row->day)->toDateString()] = (int) $row->total;
        }

        $patientVolumeChartModel = (new LineChartModel)
            ->setTitle('Patient volume')
            ->singleLine()
            ->withLegend()
            ->setDataLabelsEnabled(true)
            ->setAnimated(false)
            ->setColors(['#0d4a3c']);

        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $date = $today->copy()->subDays($daysAgo);
            $dateKey = $date->toDateString();
            $label = $date->format('D');
            $count = $volumeByDay[$dateKey] ?? 0;
            $patientVolumeChartModel->addPoint($label, $count);
        }

        $illnessStart = $today->copy()->subDays(29)->startOfDay();
        $illnessEnd = $today->copy()->endOfDay();

        $topPresentingIllnesses = DB::table('diagnosis_records')
            ->leftJoin('diagnosis_lookup', 'diagnosis_records.diagnosis_id', '=', 'diagnosis_lookup.id')
            ->leftJoin('consultations', 'diagnosis_records.consultation_id', '=', 'consultations.id')
            ->whereBetween('consultations.created_at', [$illnessStart, $illnessEnd])
            ->selectRaw("COALESCE(diagnosis_lookup.diagnosis_name, 'Unspecified') as name, COUNT(*) as total")
            ->groupBy('name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $presentingIllnessesColors = ['#0d4a3c', '#f97316', '#ec4899', '#22c55e', '#3b82f6', '#f59e0b'];
        $presentingIllnessesChartModel = (new PieChartModel)
            ->setTitle('Top presenting illnesses')
            ->asDonut()
            ->withoutLegend()
            ->setDataLabelsEnabled(false)
            ->setColors($presentingIllnessesColors);

        if ($topPresentingIllnesses->isEmpty()) {
            $presentingIllnessesChartModel->addSlice('No diagnoses', 1, '#cbd5e1');
        } else {
            foreach ($topPresentingIllnesses->values() as $index => $illness) {
                $color = $presentingIllnessesColors[$index % count($presentingIllnessesColors)];
                $presentingIllnessesChartModel->addSlice($illness->name, (int) $illness->total, $color);
            }
        }

        $doctorsOnDuty = DB::table('health_workers')->count();

        $onDutyStaff = DB::table('health_workers')
            ->select('first_name', 'last_name', 'role')
            ->orderBy('last_name')
            ->limit(5)
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

        $recentActivity = DB::table('audit_logs')
            ->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
            ->select('audit_logs.*', 'users.username')
            ->orderByDesc('audit_logs.created_at')
            ->limit(5)
            ->get()
            ->all();

        $handoutData = $user->canViewDashboardHandouts('admin')
            ? $this->loadResultsReady($request, limit: 10)
            : ['resultsReady' => collect(), 'resultsReadyCount' => 0, 'resultsFilters' => $this->emptyResultsFilters()];

        return view('dashboard', [
            'totalPatients' => $totalPatients,
            'pendingAppointments' => $pendingAppointments,
            'overdueImmunizations' => $overdueImmunizations,
            'followUpConsultationsToday' => $followUpConsultationsToday,
            'doctorsOnDuty' => $doctorsOnDuty,
            'onDutyStaff' => $onDutyStaff,
            'recentActivity' => $recentActivity,
            'patientVolumeChartModel' => $patientVolumeChartModel,
            'presentingIllnessesChartModel' => $presentingIllnessesChartModel,
            'topPresentingIllnesses' => $topPresentingIllnesses,
            'showResultsReady' => $user->canViewDashboardHandouts('admin'),
            ...$handoutData,
        ]);
    }

    private function nurseDashboard(Request $request, $user, Carbon $today)
    {
        $consultationsToday = DB::table('consultations')
            ->whereDate('created_at', $today)
            ->count();

        $pendingValidationCount = DB::table('consultations')
            ->where('status', 'nurse_review')
            ->count();

        $intakePipelineCount = DB::table('consultations')
            ->whereIn('status', ['triage', 'nurse_review'])
            ->count();

        $validationQueue = DB::table('consultations')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->where('consultations.status', 'nurse_review')
            ->orderBy('consultations.created_at')
            ->limit(8)
            ->select(
                'consultations.id',
                'consultations.created_at',
                'consultations.complaint_text',
                'patients.first_name',
                'patients.last_name'
            )
            ->get();

        $handoutData = $user->canViewDashboardHandouts('clinical')
            ? $this->loadResultsReady($request, limit: 8, defaultToToday: true)
            : ['resultsReady' => collect(), 'resultsReadyCount' => 0, 'resultsFilters' => $this->emptyResultsFilters()];

        return view('dashboard_nurse', [
            'consultationsToday' => $consultationsToday,
            'pendingValidationCount' => $pendingValidationCount,
            'intakePipelineCount' => $intakePipelineCount,
            'validationQueue' => $validationQueue,
            'showResultsReady' => $user->canViewDashboardHandouts('clinical'),
            ...$handoutData,
        ]);
    }

    private function doctorDashboard(Request $request, $user, Carbon $today)
    {
        $pendingDoctorCount = DB::table('consultations')
            ->whereIn('status', ['doctor_review', 'in_progress'])
            ->count();

        $completedConsultationsToday = DB::table('consultations')
            ->whereDate('updated_at', $today)
            ->where('status', 'completed')
            ->count();

        $consultationsToday = $pendingDoctorCount + $completedConsultationsToday;

        $followUpConsultationsToday = DB::table('consultations')
            ->whereDate('created_at', $today)
            ->where('nature_of_visit', 'Follow-up')
            ->count();

        $doctorQueue = DB::table('consultations')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->whereIn('consultations.status', ['doctor_review', 'in_progress'])
            ->orderBy('consultations.created_at')
            ->limit(8)
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
                    'status' => match ($row->status) {
                        'doctor_review' => 'Doctor Review',
                        'in_progress' => 'In progress',
                        default => ucfirst(str_replace('_', ' ', (string) $row->status)),
                    },
                    'time' => Carbon::parse($row->created_at)->diffForHumans(),
                    'complaint_text' => $row->complaint_text,
                ];
            })
            ->all();

        $handoutData = $user->canViewDashboardHandouts('clinical')
            ? $this->loadResultsReady($request, limit: 8, defaultToToday: true)
            : ['resultsReady' => collect(), 'resultsReadyCount' => 0, 'resultsFilters' => $this->emptyResultsFilters()];

        return view('dashboard_doctor', [
            'consultationsToday' => $consultationsToday,
            'pendingDoctorCount' => $pendingDoctorCount,
            'completedConsultationsToday' => $completedConsultationsToday,
            'followUpConsultationsToday' => $followUpConsultationsToday,
            'doctorQueue' => $doctorQueue,
            'showResultsReady' => $user->canViewDashboardHandouts('clinical'),
            ...$handoutData,
        ]);
    }

    /**
     * @return array{resultsReady: Collection, resultsReadyCount: int, resultsFilters: array{query: string, from: string, to: string}}
     */
    private function loadResultsReady(Request $request, int $limit = 15, bool $defaultToToday = false): array
    {
        $resultsQuery = DB::table('consultations')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->whereIn('consultations.status', ['completed', 'referred'])
            ->select(
                'consultations.id',
                'consultations.updated_at',
                'patients.first_name',
                'patients.last_name',
                'patients.id as patient_id'
            )
            ->orderByDesc('consultations.updated_at');

        if ($request->filled('results_query')) {
            $q = $request->input('results_query');
            $resultsQuery->where(function ($qb) use ($q) {
                $qb->where('patients.first_name', 'like', '%'.$q.'%')
                    ->orWhere('patients.last_name', 'like', '%'.$q.'%');
                if (is_numeric($q)) {
                    $qb->orWhere('patients.id', (int) $q);
                }
            });
        }

        $from = $request->input('results_from', $defaultToToday ? Carbon::today()->toDateString() : '');
        $to = $request->input('results_to', $defaultToToday ? Carbon::today()->toDateString() : '');

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
                'query' => $request->input('results_query', ''),
                'from' => $from,
                'to' => $to,
            ],
        ];
    }

    /**
     * @return array{query: string, from: string, to: string}
     */
    private function emptyResultsFilters(): array
    {
        return ['query' => '', 'from' => '', 'to' => ''];
    }
}
