<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vaccine extends Model
{
    protected $table = 'vaccines_lookup';

    protected $fillable = [
        'vaccine_code',
        'vaccine_name',
        'description',
        'category',
        'sort_order',
        'group_key',
        'start_after_days',
        'complete_before_days',
        'repeat_months',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'start_after_days' => 'integer',
            'complete_before_days' => 'integer',
            'repeat_months' => 'integer',
        ];
    }

    public function immunizationRecords(): HasMany
    {
        return $this->hasMany(Immunization::class, 'vaccine_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(VaccineSchedule::class, 'vaccine_id')->orderBy('dose_number');
    }
}
