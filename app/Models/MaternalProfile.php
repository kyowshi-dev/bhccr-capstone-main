<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patient_id
 * @property int|null $menarche_age
 * @property int|null $period_duration_days
 * @property int|null $cycle_interval_days
 * @property int|null $onset_sexual_intercourse_age
 * @property string|null $birth_control_method
 * @property string $menopause
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Patient $patient
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaternalProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaternalProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaternalProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaternalProfile whereBirthControlMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaternalProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaternalProfile whereCycleIntervalDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaternalProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaternalProfile whereMenarcheAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaternalProfile whereMenopause($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaternalProfile whereOnsetSexualIntercourseAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaternalProfile wherePatientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaternalProfile wherePeriodDurationDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MaternalProfile whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class MaternalProfile extends Model
{
    use LogsActivity;

    protected $fillable = [
        'patient_id',
        'menarche_age',
        'period_duration_days',
        'cycle_interval_days',
        'onset_sexual_intercourse_age',
        'birth_control_method',
        'menopause',
    ];

    public const BIRTH_CONTROL_METHODS = [
        'None',
        'Calendar/Rhythm',
        'Pills',
        'Injectable',
        'IUD',
        'Implant',
        'Condom',
        'Others',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
