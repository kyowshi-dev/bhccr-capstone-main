<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patient_id
 * @property int $vaccine_id
 * @property int|null $dose_number
 * @property string $event_type
 * @property Carbon $event_date
 * @property string|null $note
 * @property int|null $user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Patient $patient
 * @property-read Vaccine $vaccine
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImmunizationStatusEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImmunizationStatusEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImmunizationStatusEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImmunizationStatusEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImmunizationStatusEvent whereDoseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImmunizationStatusEvent whereEventDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImmunizationStatusEvent whereEventType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImmunizationStatusEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImmunizationStatusEvent whereNote($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImmunizationStatusEvent wherePatientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImmunizationStatusEvent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImmunizationStatusEvent whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ImmunizationStatusEvent whereVaccineId($value)
 *
 * @mixin \Eloquent
 */
class ImmunizationStatusEvent extends Model
{
    public const TYPE_MISSED = 'missed';

    public const TYPE_ATTENDED = 'attended';

    public const TYPE_CLEARED = 'cleared';

    protected $fillable = [
        'patient_id',
        'vaccine_id',
        'dose_number',
        'event_type',
        'event_date',
        'note',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'dose_number' => 'integer',
            'event_date' => 'date',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(Vaccine::class, 'vaccine_id');
    }
}
