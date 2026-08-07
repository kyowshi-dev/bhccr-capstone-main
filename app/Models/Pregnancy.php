<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patient_id
 * @property string $status
 * @property int $gravidity
 * @property int $parity
 * @property int $term
 * @property int $preterm
 * @property int $livebirth
 * @property int $abortion
 * @property Carbon $lmp
 * @property Carbon|null $edc
 * @property int|null $aog_weeks
 * @property string $syphilis_result
 * @property string $penicillin
 * @property Carbon|null $tt_date
 * @property bool $iron_taken
 * @property string|null $others
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Patient $patient
 * @property-read PostnatalRecord|null $postnatalRecord
 * @property-read HealthWorker|null $recordedBy
 * @property-read Collection<int, PrenatalVisit> $visits
 * @property-read int|null $visits_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy whereAbortion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy whereAogWeeks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy whereEdc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy whereGravidity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy whereIronTaken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy whereLivebirth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy whereLmp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy whereOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy whereParity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy wherePatientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy wherePenicillin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy wherePreterm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy whereRecordedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy whereSyphilisResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy whereTerm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy whereTtDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Pregnancy whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Pregnancy extends Model
{
    use LogsActivity;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'patient_id',
        'status',
        'gravidity',
        'parity',
        'term',
        'preterm',
        'livebirth',
        'abortion',
        'lmp',
        'edc',
        'aog_weeks',
        'syphilis_result',
        'penicillin',
        'tt_date',
        'iron_taken',
        'others',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'lmp' => 'date',
            'edc' => 'date',
            'tt_date' => 'date',
            'iron_taken' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(PrenatalVisit::class);
    }

    public function postnatalRecord(): HasOne
    {
        return $this->hasOne(PostnatalRecord::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(HealthWorker::class, 'recorded_by');
    }
}
