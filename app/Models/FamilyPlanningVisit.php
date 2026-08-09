<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $client_id
 * @property int|null $consultation_id
 * @property Carbon $visit_date
 * @property string $method
 * @property Carbon|null $schedule_next_visit
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FamilyPlanningClient $client
 * @property-read Consultation|null $consultation
 * @property-read HealthWorker|null $recordedBy
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningVisit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningVisit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningVisit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningVisit whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningVisit whereConsultationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningVisit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningVisit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningVisit whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningVisit whereRecordedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningVisit whereScheduleNextVisit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningVisit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FamilyPlanningVisit whereVisitDate($value)
 *
 * @mixin \Eloquent
 */
class FamilyPlanningVisit extends Model
{
    use LogsActivity;

    protected $fillable = [
        'client_id',
        'consultation_id',
        'visit_date',
        'method',
        'schedule_next_visit',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'date',
            'schedule_next_visit' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(FamilyPlanningClient::class, 'client_id');
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
