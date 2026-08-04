<?php

namespace App\Helpers;

final class PatientCode
{
    /**
     * Format a patient id as the canonical PT### display code.
     */
    public static function format(int $patientId): string
    {
        return 'PT'.str_pad((string) $patientId, 3, '0', STR_PAD_LEFT);
    }
}
