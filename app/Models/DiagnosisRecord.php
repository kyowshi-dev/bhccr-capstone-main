<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $consultation_id
 * @property int|null $diagnosis_id
 * @property string|null $remarks
 * @property int $diagnosed_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Consultation $consultation
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisRecord whereConsultationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisRecord whereDiagnosedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisRecord whereDiagnosisId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisRecord whereRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DiagnosisRecord whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class DiagnosisRecord extends Model
{
    protected $fillable = [
        'consultation_id',
        'diagnosis_id',
        'custom_diagnosis_code',
        'custom_diagnosis_name',
        'remarks',
        'diagnosed_by',
    ];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }
}
