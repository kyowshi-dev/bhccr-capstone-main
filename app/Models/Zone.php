<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $zone_number
 * @property int|null $assigned_worker_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read HealthWorker|null $assignedWorker
 * @property-read mixed $household_count
 * @property-read Collection<int, Household> $households
 * @property-read int|null $households_count
 * @property-read mixed $patient_count
 * @property-read Collection<int, Patient> $patients
 * @property-read int|null $patients_count
 * @property-read mixed $worker_name
 * @property-read mixed $worker_role
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone whereAssignedWorkerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Zone whereZoneNumber($value)
 *
 * @mixin \Eloquent
 */
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
