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
        'administered_by',
        'next_due_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'dose_number' => 'integer',
            'date_given' => 'date',
            'next_due_date' => 'date',
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
