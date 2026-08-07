<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $zone_id
 * @property string $family_name_head
 * @property string|null $contact_number
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Patient> $patients
 * @property-read int|null $patients_count
 * @property-read Zone $zone
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereContactNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereFamilyNameHead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereZoneId($value)
 *
 * @mixin \Eloquent
 */
class Household extends Model
{
    protected $fillable = [
        'zone_id',
        'family_name_head',
        'contact_number',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function patients(): HasMany
    {
        return $this->hasMany(Patient::class, 'household_id');
    }
}
