<?php

namespace App\Http\Controllers;

use App\Enums\ConsultationStatus;
use App\Helpers\PatientCode;
use App\Models\User;
use App\Services\DashboardQueryService;
use Asantibanez\LivewireCharts\Models\LineChartModel;
use Asantibanez\LivewireCharts\Models\PieChartModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $today = Carbon::today();

        $roleName = strtolower(trim((string) ($user->role?->role_name ?? $this->healthWorkerRole($user->id) ?? '')));

        return match ($roleName) {
            'bhw' => $this->bhwDashboard($request, $user, $today),
            'midwife' => $this->midwifeDashboard($request, $user, $today),
            'nurse' => $this->nurseDashboard($request, $user, $today),
            'doctor' => $this->doctorDashboard($request, $user, $today),
            default => $this->adminDashboard($request, $user, $today),
        };
    }

    private function healthWorkerRole(int $userId): ?string
    {
        return DB::table('health_workers')->where('user_id', $userId)->value('role');
    }

    private function bhwDashboard(Request $request, User $user, Carbon $today): View
    {
        $totalPatients = DashboardQueryService::totalPatients($user);
        $consultationsToday = DashboardQueryService::consultationsToday($today, $user);
        $pendingConsultations = DashboardQueryService::activeConsultations($user);

        $pendingQueue = DB::table('consultations')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->whereIn('consultations.status', ConsultationStatus::activeValues())
            ->when($user->isZoneScoped(), fn ($query) => $query->whereIn('patients.household_id', $user->accessibleHouseholdIds()))
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
                    'identifier' => PatientCode::format((int) $row->patient_id).' · '.ConsultationStatus::labelOf($row->status),
                ];
            });

        $recentActivity = DashboardQueryService::recentActivity(5, $user);

        $recentPatients = DB::table('patients')
            ->when($user->isZoneScoped(), fn ($query) => $query->whereIn('household_id', $user->accessibleHouseholdIds()))
            ->select('id', 'first_name', 'last_name')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get()
            ->map(function ($row) {
                return (object) [
                    'id' => $row->id,
                    'name' => trim($row->first_name.' '.$row->last_name),
                    'identifier' => PatientCode::format((int) $row->id),
                ];
            });

        $handoutData = $this->handoutData($request, $user, 'bhw');

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

    private function midwifeDashboard(Request $request, User $user, Carbon $today): View
    {
        $handoutData = $this->handoutData($request, $user, 'midwife', limit: 8, defaultToToday: true);

        return view('dashboard_midwife', [
            'showResultsReady' => $user->canViewDashboardHandouts('midwife'),
            ...$handoutData,
        ]);
    }

    private function nurseDashboard(Request $request, User $user, Carbon $today): View
    {
        $consultationsToday = DashboardQueryService::consultationsToday($today);

        $pendingValidationCount = DB::table('consultations')
            ->where('status', ConsultationStatus::NurseReview->value)
            ->count();

        $intakePipelineCount = DB::table('consultations')
            ->whereIn('status', [ConsultationStatus::Triage->value, ConsultationStatus::NurseReview->value])
            ->count();

        $validationQueue = DB::table('consultations')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->where('consultations.status', ConsultationStatus::NurseReview->value)
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

        $handoutData = $this->handoutData($request, $user, 'clinical', limit: 8, defaultToToday: true);

        return view('dashboard_nurse', [
            'consultationsToday' => $consultationsToday,
            'pendingValidationCount' => $pendingValidationCount,
            'intakePipelineCount' => $intakePipelineCount,
            'validationQueue' => $validationQueue,
            'showResultsReady' => $user->canViewDashboardHandouts('clinical'),
            ...$handoutData,
        ]);
    }

    private function doctorDashboard(Request $request, User $user, Carbon $today): View
    {
        $pendingDoctorCount = DB::table('consultations')
            ->whereIn('status', [ConsultationStatus::DoctorReview->value, ConsultationStatus::InProgress->value])
            ->count();

        $completedConsultationsToday = DB::table('consultations')
            ->whereDate('updated_at', $today)
            ->where('status', ConsultationStatus::Completed->value)
            ->count();

        $consultationsToday = $pendingDoctorCount + $completedConsultationsToday;

        $followUpConsultationsToday = DashboardQueryService::followUpsToday($today);

        $doctorQueue = DB::table('consultations')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->whereIn('consultations.status', [ConsultationStatus::DoctorReview->value, ConsultationStatus::InProgress->value])
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
                    'status' => ConsultationStatus::labelOf($row->status, ucfirst(str_replace('_', ' ', (string) $row->status))),
                    'time' => Carbon::parse($row->created_at)->diffForHumans(),
                    'complaint_text' => $row->complaint_text,
                ];
            })
            ->all();

        $handoutData = $this->handoutData($request, $user, 'clinical', limit: 8, defaultToToday: true);

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

    private function adminDashboard(Request $request, User $user, Carbon $today): View
    {
        $totalPatients = DashboardQueryService::totalPatients();
        $pendingAppointments = DashboardQueryService::activeConsultations();
        $overdueImmunizations = DashboardQueryService::overdueImmunizations($today);
        $followUpConsultationsToday = DashboardQueryService::followUpsToday($today);

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

        $recentActivity = DashboardQueryService::recentActivity()->all();

        $handoutData = $this->handoutData($request, $user, 'admin', limit: 10);

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

    /**
     * @return array{resultsReady: Collection, resultsReadyCount: int, resultsFilters: array{query: string, from: string, to: string}}
     */
    private function handoutData(Request $request, User $user, string $scope, int $limit = 15, bool $defaultToToday = false): array
    {
        if (! $user->canViewDashboardHandouts($scope)) {
            return [
                'resultsReady' => collect(),
                'resultsReadyCount' => 0,
                'resultsFilters' => ['query' => '', 'from' => '', 'to' => ''],
            ];
        }

        return DashboardQueryService::resultsReady([
            'query' => $request->input('results_query'),
            'from' => $request->input('results_from'),
            'to' => $request->input('results_to'),
        ], $limit, $defaultToToday, $user);
    }
}
