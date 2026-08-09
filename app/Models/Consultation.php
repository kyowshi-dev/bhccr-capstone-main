<?php

namespace App\Models;

use App\Enums\ConsultationStatus;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patient_id
 * @property int $worker_id
 * @property string $status
 * @property string|null $nurse_validated_at
 * @property int|null $nurse_validated_by
 * @property int|null $attending_doctor_id
 * @property bool $is_locked
 * @property string|null $complaint_text
 * @property string|null $nature_of_visit
 * @property string|null $notes
 * @property string $mode_of_transaction
 * @property string|null $purpose_of_visit
 * @property string|null $referred_from
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string|null $notified_at
 * @property-read HealthWorker|null $attendingDoctor
 * @property-read mixed $complaint_name
 * @property-read Collection<int, DiagnosisRecord> $diagnosisRecords
 * @property-read int|null $diagnosis_records_count
 * @property-read OutwardReferral|null $outwardReferral
 * @property-read Patient $patient
 * @property-read Collection<int, Prescription> $prescriptions
 * @property-read int|null $prescriptions_count
 * @property-read mixed $status_label
 * @property-read mixed $status_style
 * @property-read Collection<int, Vitals> $vitals
 * @property-read int|null $vitals_count
 * @property-read HealthWorker $worker
 * @property-read string|null $worker_label
 * @property-read mixed $worker_name
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereAttendingDoctorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereComplaintText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereModeOfTransaction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereNatureOfVisit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereNotifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereNurseValidatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereNurseValidatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation wherePatientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation wherePurposeOfVisit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereReferredFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Consultation whereWorkerId($value)
 *
 * @mixin \Eloquent
 */
class Consultation extends Model
{
    use LogsActivity;

    protected $fillable = [
        'patient_id',
        'worker_id',
        'attending_doctor_id',
        'status',
        'is_locked',
        'nature_of_visit',
        'mode_of_transaction',
        'purpose_of_visit',
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(HealthWorker::class, 'worker_id');
    }

    public function attendingDoctor(): BelongsTo
    {
        return $this->belongsTo(HealthWorker::class, 'attending_doctor_id');
    }

    public function outwardReferral(): HasOne
    {
        return $this->hasOne(OutwardReferral::class);
    }

    public function vitals(): HasMany
    {
        return $this->hasMany(Vitals::class, 'consultation_id');
    }

    public function diagnosisRecords(): HasMany
    {
        return $this->hasMany(DiagnosisRecord::class, 'consultation_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'consultation_id');
    }

    public function complaintName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->nature_of_visit,
        );
    }

    public function workerName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(($this->worker->first_name ?? '').' '.($this->worker->last_name ?? '')),
        );
    }

    public function workerLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => ucwords($this->worker_name) ?: 'Staff',
        );
    }

    public function statusLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => ConsultationStatus::labelOf($this->status, ucfirst(str_replace('_', ' ', (string) $this->status))),
        );
    }

    public function statusStyle(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status) {
                ConsultationStatus::Completed->value => 'background: var(--teal-soft); color: var(--primary);',
                ConsultationStatus::NurseReview->value => 'background: var(--accent-blue-soft); color: var(--accent-blue);',
                ConsultationStatus::Referred->value => 'background: var(--amber-soft); color: var(--amber);',
                default => 'background: var(--border); color: var(--ink-muted);',
            },
        );
    }
}
