<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $consultation_id
 * @property int|null $medicine_id
 * @property string|null $custom_medicine_name
 * @property int|null $quantity
 * @property string $dosage
 * @property string|null $frequency
 * @property string $duration
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Consultation $consultation
 * @property-read Medicine|null $medicine
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereConsultationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereCustomMedicineName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereDosage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereMedicineId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Prescription whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Prescription extends Model
{
    protected $fillable = [
        'consultation_id',
        'medicine_id',
        'custom_medicine_name',
        'quantity',
        'dosage',
        'frequency',
        'duration',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }
}
