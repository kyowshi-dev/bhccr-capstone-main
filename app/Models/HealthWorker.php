<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
