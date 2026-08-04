<?php

namespace App\Services;

final class ReferralService
{
    /**
     * Human-readable labels for structured referral reason keys.
     *
     * @var array<string, string>
     */
    public const REASON_LABELS = [
        'specialized_evaluation' => 'Need for specialized medical evaluation / physician',
        'lack_diagnostics' => 'Lack of diagnostic equipment / laboratory tests',
        'lack_medicines' => 'Lack of available medicines / vaccines',
        'emergency_trauma' => 'Emergency / trauma stabilization required',
    ];

    /**
     * Build the free-text "specific details" block from structured reasons
     * plus an optional detail note, or null when nothing was provided.
     *
     * @param  list<string>  $reasons
     */
    public static function specificDetails(array $reasons, ?string $details): ?string
    {
        $labels = array_filter(array_map(
            fn (string $reason): string => self::REASON_LABELS[$reason] ?? $reason,
            $reasons
        ));

        $reasonText = $labels ? 'Reasons: '.implode(', ', $labels) : '';
        $details = trim((string) $details);
        $specificDetails = trim($reasonText.($details ? "\n\n".$details : ''));

        return $specificDetails ?: null;
    }
}
