<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $vaccine_id
 * @property int $dose_number
 * @property int $min_age_days
 * @property int|null $gap_days
 * @property bool $requires_temp
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Vaccine $vaccine
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaccineSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaccineSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaccineSchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaccineSchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaccineSchedule whereDoseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaccineSchedule whereGapDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaccineSchedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaccineSchedule whereMinAgeDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaccineSchedule whereRequiresTemp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaccineSchedule whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VaccineSchedule whereVaccineId($value)
 *
 * @mixin \Eloquent
 */
class VaccineSchedule extends Model
{
    protected $table = 'vaccine_schedules';

    protected $fillable = [
        'vaccine_id',
        'dose_number',
        'min_age_days',
        'gap_days',
        'requires_temp',
    ];

    protected function casts(): array
    {
        return [
            'dose_number' => 'integer',
            'min_age_days' => 'integer',
            'gap_days' => 'integer',
            'requires_temp' => 'boolean',
        ];
    }

    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(Vaccine::class, 'vaccine_id');
    }
}
