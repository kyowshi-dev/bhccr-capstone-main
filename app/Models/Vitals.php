<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vitals extends Model
{
    protected $fillable = [
        'consultation_id',
        'phase',
        'captured_by',
        'bp_systolic',
        'bp_diastolic',
        'weight_kg',
        'height_cm',
        'temperature_c',
        'bmi',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'temperature_c' => 'decimal:2',
            'bmi' => 'decimal:2',
        ];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function summary(): Attribute
    {
        return Attribute::make(
            get: function () {
                $parts = [];
                if ($this->bp_systolic !== null || $this->bp_diastolic !== null) {
                    $parts[] = 'BP '.($this->bp_systolic ?? '—').'/'.($this->bp_diastolic ?? '—').' mmHg';
                }
                if ($this->temperature_c !== null) {
                    $parts[] = 'Temp '.$this->temperature_c.'°C';
                }
                if ($this->weight_kg !== null) {
                    $parts[] = 'Weight '.$this->weight_kg.' kg';
                }
                if ($this->height_cm !== null) {
                    $parts[] = 'Height '.$this->height_cm.' cm';
                }

                return implode(' · ', $parts);
            },
        );
    }
}
