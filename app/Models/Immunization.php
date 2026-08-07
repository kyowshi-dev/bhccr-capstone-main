<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patient_id
 * @property int $vaccine_id
 * @property int $dose_number
 * @property Carbon $date_given
 * @property numeric|null $temp_recorded
 * @property int|null $administered_by
 * @property string|null $notes
 * @property bool $no_show
 * @property Carbon|null $no_show_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Patient $patient
 * @property-read Vaccine $vaccine
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Immunization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Immunization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Immunization query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Immunization whereAdministeredBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Immunization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Immunization whereDateGiven($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Immunization whereDoseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Immunization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Immunization whereNoShow($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Immunization whereNoShowAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Immunization whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Immunization wherePatientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Immunization whereTempRecorded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Immunization whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Immunization whereVaccineId($value)
 *
 * @mixin \Eloquent
 */
class Immunization extends Model
{
    use LogsActivity;

    protected $table = 'immunization_records';

    protected $fillable = [
        'patient_id',
        'vaccine_id',
        'dose_number',
        'date_given',
        'temp_recorded',
        'administered_by',
        'notes',
        'no_show',
        'no_show_at',
    ];

    protected function casts(): array
    {
        return [
            'dose_number' => 'integer',
            'date_given' => 'date',
            'temp_recorded' => 'decimal:2',
            'no_show' => 'boolean',
            'no_show_at' => 'datetime',
        ];
    }

    public function scopeWhereReal(Builder $query): Builder
    {
        return $query->where('no_show', false);
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
