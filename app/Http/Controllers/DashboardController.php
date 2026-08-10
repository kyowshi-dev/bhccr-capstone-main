<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardChartsService;
use App\Services\DashboardQueryService;
use App\Services\MaternalQueryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();
        $today = Carbon::today();

        $role = $user->role;
        $roleName = strtolower(trim((string) ($role->role_name ?? $user->healthWorker->role ?? '')));

        return match ($roleName) {
            'bhw' => $this->bhwDashboard($request, $user, $today),
            'midwife' => $this->midwifeDashboard($request, $user, $today),
            'nurse' => $this->nurseDashboard($request, $user, $today),
            'doctor' => $this->doctorDashboard($request, $user, $today),
            default => $this->adminDashboard($request, $user, $today),
        };
    }

    private function bhwDashboard(Request $request, User $user, Carbon $today): View
    {
        $handoutData = $this->handoutData($request, $user, 'bhw');

        return view('dashboard_bhw', [
            'totalPatients' => DashboardQueryService::totalPatients($user),
            'consultationsToday' => DashboardQueryService::consultationsToday($today, $user),
            'pendingConsultations' => DashboardQueryService::activeConsultations($user),
            'pendingQueue' => DashboardQueryService::pendingQueue($user),
            'recentPatients' => DashboardQueryService::recentPatients($user),
            'queueUpdatedAt' => now()->format('M j, Y g:i A'),
            'showResultsReady' => $user->canViewDashboardHandouts('bhw'),
            ...$handoutData,
        ]);
    }

    private function midwifeDashboard(Request $request, User $user, Carbon $today): View
    {
        $handoutData = $this->handoutData($request, $user, 'midwife', limit: 8, defaultToToday: true);

        $maternalQuery = app(MaternalQueryService::class);

        return view('dashboard_midwife', [
            'showResultsReady' => $user->canViewDashboardHandouts('midwife'),
            'prenatalRegistrants' => $maternalQuery->prenatalRegistrants(),
            'dueThisMonth' => $maternalQuery->dueThisMonth($today),
            'postnatalDue' => $maternalQuery->postnatalDue($today),
            'highRiskReferrals' => $maternalQuery->highRiskReferrals(),
            'actionQueue' => $maternalQuery->actionQueue($today),
            'watchlist' => $maternalQuery->watchlist(),
            ...$handoutData,
        ]);
    }

    private function nurseDashboard(Request $request, User $user, Carbon $today): View
    {
        [
            'pendingValidationCount' => $pendingValidationCount,
            'intakePipelineCount' => $intakePipelineCount,
        ] = DashboardQueryService::nurseCounts();

        $handoutData = $this->handoutData($request, $user, 'clinical', limit: 8, defaultToToday: true);

        return view('dashboard_nurse', [
            'consultationsToday' => DashboardQueryService::consultationsToday($today),
            'pendingValidationCount' => $pendingValidationCount,
            'intakePipelineCount' => $intakePipelineCount,
            'validationQueue' => DashboardQueryService::validationQueue(),
            'showResultsReady' => $user->canViewDashboardHandouts('clinical'),
            ...$handoutData,
        ]);
    }

    private function doctorDashboard(Request $request, User $user, Carbon $today): View
    {
        [
            'pendingDoctorCount' => $pendingDoctorCount,
            'completedConsultationsToday' => $completedConsultationsToday,
            'consultationsToday' => $consultationsToday,
        ] = DashboardQueryService::doctorCounts($today);

        $handoutData = $this->handoutData($request, $user, 'clinical', limit: 8, defaultToToday: true);

        return view('dashboard_doctor', [
            'consultationsToday' => $consultationsToday,
            'pendingDoctorCount' => $pendingDoctorCount,
            'completedConsultationsToday' => $completedConsultationsToday,
            'followUpConsultationsToday' => DashboardQueryService::followUpsToday($today),
            'doctorQueue' => DashboardQueryService::doctorQueue(),
            'showResultsReady' => $user->canViewDashboardHandouts('clinical'),
            ...$handoutData,
        ]);
    }

    private function adminDashboard(Request $request, User $user, Carbon $today): View
    {
        $volumeStart = $today->copy()->subDays(6)->startOfDay();
        $illnessStart = $today->copy()->subDays(29)->startOfDay();
        $rangeEnd = $today->copy()->endOfDay();

        $patientVolumeChartModel = DashboardChartsService::patientVolumeChart(
            $today,
            DashboardQueryService::patientVolumeByDay($volumeStart, $rangeEnd)
        );

        $topPresentingIllnesses = DashboardQueryService::topIllnesses($illnessStart, $rangeEnd);
        $presentingIllnessesChartModel = DashboardChartsService::presentingIllnessesChart($topPresentingIllnesses);

        [
            'doctorsOnDuty' => $doctorsOnDuty,
            'onDutyStaff' => $onDutyStaff,
        ] = DashboardQueryService::onDuty();

        $handoutData = $this->handoutData($request, $user, 'admin', limit: 10);

        return view('dashboard', [
            'totalPatients' => DashboardQueryService::totalPatients(),
            'pendingAppointments' => DashboardQueryService::activeConsultations(),
            'overdueImmunizations' => DashboardQueryService::overdueImmunizations($today),
            'followUpConsultationsToday' => DashboardQueryService::followUpsToday($today),
            'doctorsOnDuty' => $doctorsOnDuty,
            'onDutyStaff' => $onDutyStaff,
            'recentActivity' => DashboardQueryService::recentActivity()->all(),
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
