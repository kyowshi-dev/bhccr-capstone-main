<?php

namespace App\Factories;

use App\DTOs\MaternalQueueDTO;
use App\Models\Patient;
use App\Models\PostnatalRecord;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MaternalQueueDTOFactory
{
    public static function patientName(Patient $patient): string
    {
        return trim($patient->last_name.', '.$patient->first_name.' '.($patient->middle_initial ?? ''));
    }

    public static function fromPregnancies(Collection $records, array $activeConsultationMap): Collection
    {
        return $records->map(function ($pregnancy) use ($activeConsultationMap) {
            $patient = $pregnancy->patient;
            $latestVisit = $pregnancy->visits->sortByDesc('visit_date')->first();
            $nextVisitDate = $latestVisit?->next_visit_date;
            $lastVisitDate = $latestVisit?->visit_date;
            $today = Carbon::today();
            $isOverdue = $nextVisitDate && $nextVisitDate->lt($today);
            $hasActiveConsultation = isset($activeConsultationMap[$patient->id]);
            $isHighRisk = ! empty($pregnancy->risk_flags);

            if ($latestVisit) {
                $subtitle = 'Last Checkup: '.$lastVisitDate->format('M j');
                $subtitle .= $nextVisitDate
                    ? ' | Next Due: '.$nextVisitDate->format('M j')
                    : ' | No next visit scheduled';
            } else {
                $subtitle = 'New registration - schedule first visit';
            }

            return new MaternalQueueDTO(
                patient_id: $patient->id,
                patient_name: self::patientName($patient),
                patient_code: $patient->patient_code,
                program_type: 'prenatal',
                risk_level: $isHighRisk ? 'high' : 'normal',
                due_date: $nextVisitDate?->toDateString(),
                primary_subtitle: $subtitle,
                is_checked_in_today: $hasActiveConsultation,
                has_active_consultation: $hasActiveConsultation,
                active_consultation_id: $activeConsultationMap[$patient->id] ?? null,
                consultation_id: null,
                program_record_id: $pregnancy->id,
                context_badges: [],
                is_overdue: (bool) $isOverdue,
            );
        });
    }

    public static function fromPostnatalRecords(Collection $records, array $activeConsultationMap): Collection
    {
        $today = Carbon::today();

        return $records->map(function ($record) use ($activeConsultationMap, $today) {
            $patient = $record->patient;
            $deliveryDate = $record->delivery_date;
            $hasActiveConsultation = isset($activeConsultationMap[$patient->id]);
            $hasDangerSigns = ! empty($record->danger_signs_mother);

            $slots = PostnatalRecord::POSTPARTUM_SLOTS;

            $earliestOpenSlot = null;
            $isOverdue = false;

            foreach ($slots as $column => $days) {
                if ($record->{$column} !== null) {
                    continue;
                }

                $slotDueDate = $deliveryDate->copy()->addDays($days);

                if ($slotDueDate->lte($today)) {
                    $isOverdue = true;
                }

                if (! $earliestOpenSlot || $slotDueDate->lt($earliestOpenSlot['due_date'])) {
                    $earliestOpenSlot = [
                        'label' => $days === 1 ? '24-hour' : $days.'-day',
                        'due_date' => $slotDueDate,
                    ];
                }
            }

            $subtitle = 'Delivered: '.$deliveryDate->format('M j');
            $subtitle .= $earliestOpenSlot
                ? ' | Next: '.$earliestOpenSlot['label'].' Follow-up'
                : ' | All follow-ups complete';

            return new MaternalQueueDTO(
                patient_id: $patient->id,
                patient_name: self::patientName($patient),
                patient_code: $patient->patient_code,
                program_type: 'postnatal',
                risk_level: $hasDangerSigns ? 'high' : 'normal',
                due_date: ($earliestOpenSlot['due_date'] ?? null)?->toDateString(),
                primary_subtitle: $subtitle,
                is_checked_in_today: $hasActiveConsultation,
                has_active_consultation: $hasActiveConsultation,
                active_consultation_id: $activeConsultationMap[$patient->id] ?? null,
                consultation_id: $record->consultation_id,
                program_record_id: $record->id,
                context_badges: [],
                is_overdue: $isOverdue,
            );
        })->filter(fn (MaternalQueueDTO $dto) => $dto->due_date !== null);
    }

    public static function fromFamilyPlanningClients(Collection $records, array $activeConsultationMap): Collection
    {
        $today = Carbon::today();

        return $records->map(function ($client) use ($activeConsultationMap, $today) {
            $patient = $client->patient;
            $nextVisit = $client->schedule_next_visit;
            $hasActiveConsultation = isset($activeConsultationMap[$patient->id]);
            $isOverdue = $nextVisit && $nextVisit->lt($today);

            $subtitle = 'Method: '.$client->method;
            $subtitle .= $nextVisit
                ? ' | Next Resupply: '.$nextVisit->format('M j')
                : ' | No next visit scheduled';

            return new MaternalQueueDTO(
                patient_id: $patient->id,
                patient_name: self::patientName($patient),
                patient_code: $patient->patient_code,
                program_type: 'fp',
                risk_level: 'normal',
                due_date: $nextVisit?->toDateString(),
                primary_subtitle: $subtitle,
                is_checked_in_today: $hasActiveConsultation,
                has_active_consultation: $hasActiveConsultation,
                active_consultation_id: $activeConsultationMap[$patient->id] ?? null,
                consultation_id: null,
                program_record_id: $client->id,
                context_badges: [],
                is_overdue: (bool) $isOverdue,
            );
        });
    }

    public static function fromConsultations(Collection $records): Collection
    {
        return $records->map(function ($consultation) {
            $patient = $consultation->patient;
            $complaint = $consultation->complaint_text ?: $consultation->purpose_of_visit ?: 'General consultation';

            $subtitle = 'Check-in: '.$consultation->created_at->format('g:i A');
            $subtitle .= ' | '.$complaint;

            return new MaternalQueueDTO(
                patient_id: $patient->id,
                patient_name: self::patientName($patient),
                patient_code: $patient->patient_code,
                program_type: 'triage',
                risk_level: 'normal',
                due_date: $consultation->created_at->toDateString(),
                primary_subtitle: $subtitle,
                is_checked_in_today: true,
                has_active_consultation: true,
                active_consultation_id: $consultation->id,
                consultation_id: $consultation->id,
                program_record_id: null,
                context_badges: [],
                is_overdue: false,
                escalated: ! empty($consultation->escalated_at),
            );
        });
    }
}
