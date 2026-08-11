<?php

namespace App\DTOs;

use Illuminate\Support\Collection;

final class MaternalQueueDTO
{
    public function __construct(
        public readonly int $patient_id,
        public readonly string $patient_name,
        public readonly string $patient_code,
        public readonly string $program_type,
        public readonly string $risk_level,
        public readonly ?string $due_date,
        public readonly string $primary_subtitle,
        public readonly bool $is_checked_in_today,
        public readonly bool $has_active_consultation,
        public readonly ?int $active_consultation_id,
        public readonly ?int $consultation_id,
        public readonly ?int $program_record_id,
        public readonly array $context_badges,
        public readonly bool $is_grouped = false,
        public bool $is_overdue = false,
        public bool $escalated = false,
    ) {}

    public static function forGroupedCard(Collection $items): ?self
    {
        $primary = $items->sortBy('due_date')->first();

        if (! $primary) {
            return null;
        }

        $badges = [];
        $hasMultiplePrograms = $items->count() > 1;

        foreach ($items as $item) {
            if ($hasMultiplePrograms || $item->program_type !== $primary->program_type) {
                $badges[] = [
                    'program_type' => $item->program_type,
                    'label' => $item->badgeLabel(),
                    'state' => $item->is_overdue ? 'overdue' : 'due',
                ];
            }
        }

        return new self(
            patient_id: $primary->patient_id,
            patient_name: $primary->patient_name,
            patient_code: $primary->patient_code,
            program_type: $primary->program_type,
            risk_level: $items->contains(fn ($i) => $i->risk_level === 'high') ? 'high' : 'normal',
            due_date: $primary->due_date,
            primary_subtitle: $primary->primary_subtitle,
            is_checked_in_today: $primary->is_checked_in_today,
            has_active_consultation: $items->contains(fn ($i) => $i->has_active_consultation),
            active_consultation_id: $items->firstWhere('has_active_consultation', true)?->active_consultation_id,
            consultation_id: $primary->consultation_id,
            program_record_id: $primary->program_record_id,
            context_badges: $badges,
            is_grouped: true,
            is_overdue: $primary->is_overdue,
            escalated: $items->contains(fn ($i) => $i->escalated),
        );
    }

    public function badgeLabel(): string
    {
        return match ($this->program_type) {
            'prenatal' => $this->is_overdue ? 'Prenatal Overdue' : 'Prenatal Due',
            'postnatal' => $this->is_overdue ? 'Postnatal Overdue' : 'Postnatal Due',
            'fp' => $this->is_overdue ? 'FP Overdue' : 'FP Due',
            default => 'Walk-in',
        };
    }
}
