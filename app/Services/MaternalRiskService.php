<?php

namespace App\Services;

use App\Models\Pregnancy;

final class MaternalRiskService
{
    public static function shouldEscalate(array $vitals, ?Pregnancy $pregnancy): bool
    {
        $bpSystolic = $vitals['bp_systolic'] ?? null;
        $bpDiastolic = $vitals['bp_diastolic'] ?? null;
        $temp = $vitals['temperature'] ?? null;

        if ($bpSystolic !== null && $bpSystolic >= 140) {
            return true;
        }

        if ($bpDiastolic !== null && $bpDiastolic >= 90) {
            return true;
        }

        if ($temp !== null && $temp >= 38.0) {
            return true;
        }

        if ($pregnancy !== null && ! empty($pregnancy->risk_flags)) {
            return true;
        }

        return false;
    }

    public static function escalatedAt(array $vitals, ?Pregnancy $pregnancy): ?string
    {
        return self::shouldEscalate($vitals, $pregnancy) ? now()->toDateTimeString() : null;
    }
}
