<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string $role
 * @property string|null $contact_number
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Consultation> $consultations
 * @property-read int|null $consultations_count
 * @property-read mixed $name
 * @property-read User $user
 * @property-read Collection<int, Zone> $zones
 * @property-read int|null $zones_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthWorker newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthWorker newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthWorker query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthWorker whereContactNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthWorker whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthWorker whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthWorker whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthWorker whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthWorker whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthWorker whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthWorker whereUserId($value)
 *
 * @mixin \Eloquent
 */
class HealthWorker extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'role',
        'contact_number',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class, 'assigned_worker_id');
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'worker_id');
    }

    public function name(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(($this->first_name ?? '').' '.($this->last_name ?? '')),
        );
    }

    public function isDoctor(): bool
    {
        return strtolower((string) $this->role) === 'doctor';
    }

    public function isNurse(): bool
    {
        return strtolower((string) $this->role) === 'nurse';
    }

    public function isMidwife(): bool
    {
        return strtolower((string) $this->role) === 'midwife';
    }

    public function isBhw(): bool
    {
        return strtolower((string) $this->role) === 'bhw';
    }

    public function isClinical(): bool
    {
        return $this->isDoctor() || $this->isNurse();
    }
}
