<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

use App\Models\OutwardReferral;

class Consultation extends Model
{
    protected $fillable = [
        'patient_id',
        'worker_id',
        'status',
        'is_locked',
        'chief_complaint_id',
        'nature_of_visit',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(HealthWorker::class, 'worker_id');
    }

    public function chiefComplaint()
    {
        return $this->belongsTo(ComplaintLookup::class, 'chief_complaint_id');
    }

    public function outwardReferral(): HasOne
    {
        return $this->hasOne(OutwardReferral::class);
    }
}
