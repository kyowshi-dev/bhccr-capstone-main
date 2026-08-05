<?php

namespace App\Models;

use App\Enums\ConsultationStatus;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Consultation extends Model
{
    use LogsActivity;

    protected $fillable = [
        'patient_id',
        'worker_id',
        'status',
        'is_locked',
        'nature_of_visit',
        'mode_of_transaction',
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
            get: fn () => $this->worker ? trim(($this->worker->first_name ?? '').' '.($this->worker->last_name ?? '')) : null,
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
