<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosisRecord extends Model
{
    protected $fillable = [
        'consultation_id',
        'diagnosis_id',
        'remarks',
        'diagnosed_by',
    ];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }
}
