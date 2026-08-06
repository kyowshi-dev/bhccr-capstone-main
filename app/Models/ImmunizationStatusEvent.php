<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImmunizationStatusEvent extends Model
{
    public const TYPE_MISSED = 'missed';

    public const TYPE_ATTENDED = 'attended';

    public const TYPE_CLEARED = 'cleared';

    protected $fillable = [
        'patient_id',
        'vaccine_id',
        'dose_number',
        'event_type',
        'event_date',
        'note',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'dose_number' => 'integer',
            'event_date' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(Vaccine::class, 'vaccine_id');
    }
}
