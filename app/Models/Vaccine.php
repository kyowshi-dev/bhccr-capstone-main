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
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function immunizationRecords(): HasMany
    {
        return $this->hasMany(Immunization::class, 'vaccine_id');
    }
}
