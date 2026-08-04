<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Zone extends Model
{
    use LogsActivity;

    protected $table = 'zones';

    protected $fillable = [
        'zone_number',
        'assigned_worker_id',
    ];

    public function assignedWorker(): BelongsTo
    {
        return $this->belongsTo(HealthWorker::class, 'assigned_worker_id');
    }

    public function households(): HasMany
    {
        return $this->hasMany(Household::class, 'zone_id');
    }

    public function patients(): HasManyThrough
    {
        return $this->hasManyThrough(Patient::class, Household::class);
    }

    public function workerName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->assignedWorker?->name,
        );
    }

    public function workerRole(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->assignedWorker?->role,
        );
    }

    public function householdCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->households()->count(),
        );
    }

    public function patientCount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->patients()->count(),
        );
    }
}
