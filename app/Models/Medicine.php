<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medicine extends Model
{
    use LogsActivity;

    protected $table = 'medicines_lookup';

    protected $fillable = [
        'name',
        'generic_name',
        'strength',
        'form',
        'manufacturer',
        'expiration_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'expiration_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'medicine_id');
    }
}
