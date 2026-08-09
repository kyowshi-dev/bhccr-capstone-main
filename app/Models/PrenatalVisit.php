<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $pregnancy_id
 * @property int|null $consultation_id
 * @property Carbon $visit_date
 * @property numeric|null $fundic_height_cm
 * @property int|null $fetal_heart_tone_bpm
 * @property Carbon|null $next_visit_date
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Pregnancy $pregnancy
 * @property-read Consultation|null $consultation
 * @property-read HealthWorker|null $recordedBy
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrenatalVisit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrenatalVisit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrenatalVisit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrenatalVisit whereConsultationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrenatalVisit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrenatalVisit whereFetalHeartToneBpm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrenatalVisit whereFundicHeightCm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrenatalVisit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrenatalVisit whereNextVisitDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrenatalVisit wherePregnancyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrenatalVisit whereRecordedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrenatalVisit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PrenatalVisit whereVisitDate($value)
 *
 * @mixin \Eloquent
 */
class PrenatalVisit extends Model
{
    use LogsActivity;

    protected $fillable = [
        'pregnancy_id',
        'consultation_id',
        'visit_date',
        'fundic_height_cm',
        'fetal_heart_tone_bpm',
        'next_visit_date',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'fundic_height_cm' => 'decimal:1',
            'next_visit_date' => 'date',
        ];
    }

    public function pregnancy(): BelongsTo
    {
        return $this->belongsTo(Pregnancy::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(HealthWorker::class, 'recorded_by');
    }
}
