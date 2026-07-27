<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $table = 'zones';

    protected $fillable = [
        'zone_number',
        'assigned_worker_id',
    ];

    public function assignedWorker()
    {
        return $this->belongsTo(HealthWorker::class, 'assigned_worker_id');
    }

    public function households()
    {
        return $this->hasMany(Household::class, 'zone_id');
    }

    public function patients()
    {
        return $this->hasManyThrough(Patient::class, Household::class);
    }
}
