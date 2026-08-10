<?php

namespace App\Services;

use App\DTOs\MaternalQueueDTO;
use App\Enums\ConsultationStatus;
use App\Factories\MaternalQueueDTOFactory;
use App\Models\Consultation;
use App\Models\FamilyPlanningClient;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use App\Models\Pregnancy;
use App\Models\WatchlistEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MaternalQueueAggregatorService
{
    public function aggregate(): Collection
    {
        return Cache::remember('maternal_queue_aggregate', now()->addMinutes(5), function () {
            return $this->buildCollection();
        });
    }

    private function buildCollection(): Collection
    {
        $today = Carbon::today();
        $sevenDaysFromNow = $today->copy()->addDays(7);

        $todayTriage = $this->todayTriage($today);
        $activeConsultationMap = $todayTriage->pluck('id', 'patient_id')->toArray();

        $activePregnancies = $this->activePregnancies();
        $openPostnatalSlots = $this->openPostnatalSlots($today);
        $fpDueSoon = $this->fpDueSoon($sevenDaysFromNow);

        return collect()
            ->merge(MaternalQueueDTOFactory::fromPregnancies($activePregnancies, $activeConsultationMap))
            ->merge(MaternalQueueDTOFactory::fromPostnatalRecords($openPostnatalSlots, $activeConsultationMap))
            ->merge(MaternalQueueDTOFactory::fromFamilyPlanningClients($fpDueSoon, $activeConsultationMap))
            ->merge(MaternalQueueDTOFactory::fromConsultations($todayTriage, $activeConsultationMap))
            ->merge($this->watchlistDtos($activeConsultationMap))
            ->sortBy('due_date')
            ->values();
    }

    public function kpis(): array
    {
        return Cache::remember('maternal_queue_kpis', now()->addMinutes(5), function () {
            $today = Carbon::today();

            return [
                'prenatalRegistrants' => Pregnancy::where('status', Pregnancy::STATUS_ACTIVE)->count(),
                'dueThisMonth' => Pregnancy::where('status', Pregnancy::STATUS_ACTIVE)
                    ->whereBetween('edc', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
                    ->count(),
                'postnatalDue' => $this->countOpenPostnatalSlots($today),
                'highRiskReferrals' => PostnatalRecord::whereNotNull('danger_signs_mother')
                    ->where('danger_signs_mother', '!=', '[]')
                    ->count(),
                'fpScheduled' => FamilyPlanningClient::where('is_active', true)
                    ->where('schedule_next_visit', '<=', $today->copy()->addDays(7)->toDateString())
                    ->count(),
            ];
        });
    }

    private function activePregnancies(): Collection
    {
        return Pregnancy::query()
            ->where('status', Pregnancy::STATUS_ACTIVE)
            ->with(['patient', 'visits' => fn ($q) => $q->orderByDesc('visit_date')->limit(1)])
            ->orderBy('edc')
            ->get();
    }

    private function openPostnatalSlots(Carbon $today): Collection
    {
        return PostnatalRecord::query()
            ->where('delivery_date', '<=', $today)
            ->where(function ($query) {
                $query
                    ->whereNull('postpartum_24h_date')
                    ->orWhereNull('postpartum_7d_date')
                    ->orWhereNull('postpartum_14d_date')
                    ->orWhereNull('postpartum_28d_date');
            })
            ->with('patient')
            ->orderBy('delivery_date')
            ->get();
    }

    private function fpDueSoon(Carbon $sevenDaysFromNow): Collection
    {
        return FamilyPlanningClient::query()
            ->where('is_active', true)
            ->where(function ($query) use ($sevenDaysFromNow) {
                $query
                    ->where('schedule_next_visit', '<=', $sevenDaysFromNow)
                    ->orWhereNull('schedule_next_visit');
            })
            ->with('patient')
            ->orderBy('schedule_next_visit')
            ->get();
    }

    private function todayTriage(Carbon $today): Collection
    {
        return Consultation::query()
            ->whereIn('status', ConsultationStatus::activeValues())
            ->whereDate('created_at', $today)
            ->with('patient')
            ->orderByDesc('created_at')
            ->get();
    }

    private function countOpenPostnatalSlots(Carbon $today): int
    {
        return $this->openPostnatalSlots($today)->count();
    }

    private function watchlistDtos(array $activeConsultationMap): Collection
    {
        $entries = WatchlistEntry::query()
            ->active()
            ->with('patient')
            ->orderByDesc('flagged_at')
            ->get();

        return $entries->map(function (WatchlistEntry $entry) use ($activeConsultationMap) {
            /** @var Patient $patient */
            $patient = $entry->patient;
            $hasActiveConsultation = isset($activeConsultationMap[$patient->id]);

            return new MaternalQueueDTO(
                patient_id: $patient->id,
                patient_name: trim($patient->last_name.', '.$patient->first_name.' '.($patient->middle_initial ?? '')),
                patient_code: $patient->patient_code,
                program_type: $entry->program_type,
                risk_level: 'high',
                due_date: Carbon::parse($entry->flagged_at)->toDateString(),
                primary_subtitle: 'Watchlist: '.str_replace('_', ' ', ucfirst($entry->reason_code)),
                is_checked_in_today: $hasActiveConsultation,
                has_active_consultation: $hasActiveConsultation,
                active_consultation_id: $activeConsultationMap[$patient->id] ?? null,
                consultation_id: null,
                program_record_id: $entry->id,
                context_badges: [],
                is_overdue: true,
            );
        });
    }
}
