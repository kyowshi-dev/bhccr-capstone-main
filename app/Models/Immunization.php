<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Immunization extends Model
{
    use LogsActivity;

    protected $table = 'immunization_records';

    protected $fillable = [
        'patient_id',
        'vaccine_id',
        'dose_number',
        'date_given',
        'temp_recorded',
        'administered_by',
        'next_due_date',
        'notes',
        'no_show',
        'no_show_at',
    ];

    protected function casts(): array
    {
        return [
            'dose_number' => 'integer',
            'date_given' => 'date',
            'temp_recorded' => 'decimal:2',
            'next_due_date' => 'date',
            'no_show' => 'boolean',
            'no_show_at' => 'datetime',
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
