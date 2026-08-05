<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VaccineSchedule extends Model
{
    protected $table = 'vaccine_schedules';

    protected $fillable = [
        'vaccine_id',
        'dose_number',
        'min_age_days',
        'gap_days',
        'requires_temp',
    ];

    protected function casts(): array
    {
        return [
            'dose_number' => 'integer',
            'min_age_days' => 'integer',
            'gap_days' => 'integer',
            'requires_temp' => 'boolean',
        ];
    }

    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(Vaccine::class, 'vaccine_id');
    }
}
