<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $consultation_id
 * @property string $destination_facility
 * @property string $pertinent_history
 * @property string|null $actions_taken
 * @property string|null $specific_details
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Consultation $consultation
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutwardReferral newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutwardReferral newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutwardReferral query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutwardReferral whereActionsTaken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutwardReferral whereConsultationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutwardReferral whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutwardReferral whereDestinationFacility($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutwardReferral whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutwardReferral wherePertinentHistory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutwardReferral whereSpecificDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutwardReferral whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutwardReferral whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class OutwardReferral extends Model
{
    use LogsActivity;

    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_NO_SHOW = 'no_show';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_COMPLETED,
        self::STATUS_NO_SHOW,
        self::STATUS_CANCELLED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_NO_SHOW => 'No-Show',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    protected $fillable = [
        'consultation_id',
        'destination_facility',
        'pertinent_history',
        'actions_taken',
        'specific_details',
        'status',
    ];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }
}
