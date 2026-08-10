<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchlistEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'patient_id',
        'program_type',
        'reason_code',
        'notes',
        'flagged_by',
        'flagged_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'flagged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function flaggedBy(): BelongsTo
    {
        return $this->belongsTo(HealthWorker::class, 'flagged_by');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('resolved_at');
    }
}
