<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $consultation_id
 * @property string $phase
 * @property int|null $captured_by
 * @property string|null $bp_systolic
 * @property string|null $bp_diastolic
 * @property numeric|null $weight_kg
 * @property numeric|null $height_cm
 * @property numeric|null $temperature_c
 * @property numeric|null $bmi
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Consultation $consultation
 * @property-read mixed $summary
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vitals newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vitals newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vitals query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vitals whereBmi($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vitals whereBpDiastolic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vitals whereBpSystolic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vitals whereCapturedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vitals whereConsultationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vitals whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vitals whereHeightCm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vitals whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vitals whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vitals wherePhase($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vitals whereTemperatureC($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vitals whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vitals whereWeightKg($value)
 *
 * @mixin \Eloquent
 */
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
                    $parts[] = 'BP '.($this->bp_systolic ?? '-').'/'.($this->bp_diastolic ?? '-').' mmHg';
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
