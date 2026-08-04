<?php

namespace App\Enums;

enum ConsultationStatus: string
{
    case Triage = 'triage';
    case NurseReview = 'nurse_review';
    case DoctorReview = 'doctor_review';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Referred = 'referred';

    /**
     * Human-readable label for UI badges and filters.
     */
    public function label(): string
    {
        return match ($this) {
            self::Triage => 'Triage',
            self::NurseReview => 'Nurse Review',
            self::DoctorReview => 'Doctor Review',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::Referred => 'Referred',
        };
    }

    /**
     * Statuses still being worked on (not yet terminal).
     *
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return [
            self::Triage->value,
            self::NurseReview->value,
            self::DoctorReview->value,
            self::InProgress->value,
        ];
    }

    /**
     * Terminal statuses where the consultation is finished.
     *
     * @return list<string>
     */
    public static function terminalValues(): array
    {
        return [
            self::Completed->value,
            self::Referred->value,
        ];
    }

    /**
     * Resolve an optional status value to its label, or a fallback.
     */
    public static function labelOf(?string $status, string $fallback = 'Unknown'): string
    {
        return self::tryFrom((string) $status)?->label() ?? $fallback;
    }
}
