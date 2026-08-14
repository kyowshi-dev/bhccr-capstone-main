<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
 * @property-read Vitals|null $triage_vitals
 * @property-read array{bp: string, weight_kg: string|null, temperature_c: string|null, fundic_height_cm: string|null} $flowsheet_trend
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

    /**
     * Vitals captured during this visit's intake (triage phase),
     * falling back to the latest vitals version on the consultation.
     */
    public function triageVitals(): Attribute
    {
        return Attribute::make(
            get: function (): ?Vitals {
                $vitals = $this->consultation?->vitals;

                return $vitals?->firstWhere('phase', 'triage')
                    ?? $vitals?->sortByDesc('created_at')->first();
            },
        );
    }

    /**
     * Display-ready values for the prenatal flowsheet row, using raw
     * stored values so precision is shown exactly as entered.
     */
    public function flowsheetTrend(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                $vitals = $this->triage_vitals;

                return [
                    'bp' => ($vitals?->bp_systolic !== null || $vitals?->bp_diastolic !== null)
                        ? ($vitals->bp_systolic ?? '-').'/'.($vitals->bp_diastolic ?? '-')
                        : '-',
                    'weight_kg' => $vitals?->rawDisplay('weight_kg'),
                    'temperature_c' => $vitals?->rawDisplay('temperature_c'),
                    'fundic_height_cm' => $this->rawNumber('fundic_height_cm'),
                ];
            },
        );
    }

    /**
     * Trim a raw numeric attribute for display, removing trailing
     * fractional zeros only.
     */
    private function rawNumber(string $column): ?string
    {
        $raw = $this->getRawOriginal($column);

        if ($raw === null || $raw === '') {
            return null;
        }

        $value = (string) $raw;

        if (str_contains($value, '.')) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        return $value === '' ? '0' : $value;
    }
}
