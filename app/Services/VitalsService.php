<?php

namespace App\Services;

final class VitalsService
{
    /**
     * Validation rules shared by all vitals entry points.
     *
     * @return array<string, list<string>>
     */
    public static function rules(bool $required = false): array
    {
        $presence = $required ? 'required' : 'nullable';

        return [
            'bp_systolic' => [$presence, 'numeric', 'min:0', 'max:300'],
            'bp_diastolic' => [$presence, 'numeric', 'min:0', 'max:200'],
            'temperature' => [$presence, 'numeric', 'min:30', 'max:45'],
            'weight' => [$presence, 'numeric', 'min:0', 'max:500'],
            'height' => [$presence, 'numeric', 'min:0', 'max:300'],
        ];
    }

    /**
     * Map the form-facing input keys onto the vitals table columns.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function fromInput(array $input): array
    {
        return [
            'bp_systolic' => $input['bp_systolic'] ?? null,
            'bp_diastolic' => $input['bp_diastolic'] ?? null,
            'weight_kg' => $input['weight'] ?? null,
            'height_cm' => $input['height'] ?? null,
            'temperature_c' => $input['temperature'] ?? null,
        ];
    }
}
