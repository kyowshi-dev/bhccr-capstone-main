<?php

namespace App\Support;

class VitalsThresholds
{
    public static function isCritical(?object $vitals): bool
    {
        if ($vitals === null) {
            return false;
        }

        $temp = (float) ($vitals->temperature_c ?? 0);
        $sys = (int) ($vitals->bp_systolic ?? 0);
        $dia = (int) ($vitals->bp_diastolic ?? 0);

        return $temp > 37.5 || $sys > 140 || $dia > 90;
    }
}
