<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patient_id
 * @property string $type_of_client
 * @property string $method
 * @property string|null $drop_out_reason
 * @property Carbon|null $schedule_next_visit
 * @property bool $is_active
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Patient $patient
 * @property-read HealthWorker|null $recordedBy
 * @property-read Collection<int, FamilyPlanningVisit> $visits
 * @property-read int|null $visits_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningClient newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningClient newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningClient query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningClient whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningClient whereDropOutReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningClient whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningClient whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningClient whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningClient wherePatientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningClient whereRecordedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningClient whereScheduleNextVisit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningClient whereTypeOfClient($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningClient whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class FamilyPlanningClient extends Model
{
    use LogsActivity;

    public const TYPE_NEW_ACCEPTOR = 'new_acceptor';

    public const TYPE_CONTINUING_USER = 'continuing_user';

    public const TYPE_DROP_OUT = 'drop_out';

    public const TYPE_OTHERS = 'others';

    public const TYPES = [
        self::TYPE_NEW_ACCEPTOR => 'New Acceptor',
        self::TYPE_CONTINUING_USER => 'Continuing User',
        self::TYPE_DROP_OUT => 'Drop Out',
        self::TYPE_OTHERS => 'Others',
    ];

    public const METHODS = [
        'Pills',
        'Injectable',
        'DMPA',
        'Implant',
        'IUD',
        'Condom',
        'BTL',
        'Calendar/Rhythm',
        'LAM',
        'Others',
    ];

    protected $fillable = [
        'patient_id',
        'type_of_client',
        'method',
        'drop_out_reason',
        'schedule_next_visit',
        'is_active',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'schedule_next_visit' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(FamilyPlanningVisit::class, 'client_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(HealthWorker::class, 'recorded_by');
    }
}
