<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $vaccine_code
 * @property string $vaccine_name
 * @property string|null $description
 * @property string $category
 * @property string|null $group_key
 * @property int|null $start_after_days
 * @property int|null $complete_before_days
 * @property int|null $repeat_months
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, Immunization> $immunizationRecords
 * @property-read int|null $immunization_records_count
 * @property-read Collection<int, VaccineSchedule> $schedules
 * @property-read int|null $schedules_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vaccine newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vaccine newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vaccine query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vaccine whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vaccine whereCompleteBeforeDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vaccine whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vaccine whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vaccine whereGroupKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vaccine whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vaccine whereRepeatMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vaccine whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vaccine whereStartAfterDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vaccine whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vaccine whereVaccineCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Vaccine whereVaccineName($value)
 *
 * @mixin \Eloquent
 */
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
