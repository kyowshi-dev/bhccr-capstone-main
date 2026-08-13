<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patient_id
 * @property int|null $pregnancy_id
 * @property int|null $consultation_id
 * @property string $pregnancy_outcome
 * @property int|null $prenatal_visits_completed
 * @property string $place_delivered
 * @property string $mode_of_delivery
 * @property string $attendant_at_birth
 * @property Carbon $delivery_date
 * @property string $delivery_time
 * @property Carbon $breastfeeding_date
 * @property string $breastfeeding_time
 * @property Carbon|null $postpartum_24h_date
 * @property Carbon|null $postpartum_7d_date
 * @property Carbon|null $postpartum_14d_date
 * @property Carbon|null $postpartum_28d_date
 * @property array<array-key, mixed>|null $danger_signs_mother
 * @property array<array-key, mixed>|null $danger_signs_baby
 * @property Carbon|null $vitamin_a_date
 * @property Carbon|null $iron_date
 * @property int|null $iron_count
 * @property string $child_last_name
 * @property string $child_first_name
 * @property string|null $child_middle_name
 * @property string $child_sex
 * @property numeric|null $child_birth_length_cm
 * @property numeric|null $child_birth_weight_kg
 * @property int|null $child_patient_id
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Patient|null $childPatient
 * @property-read Consultation|null $consultation
 * @property-read Patient $patient
 * @property-read Pregnancy|null $pregnancy
 * @property-read HealthWorker|null $recordedBy
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereAttendantAtBirth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereBreastfeedingDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereBreastfeedingTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereChildBirthLengthCm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereChildBirthWeightKg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereChildFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereChildLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereChildMiddleName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereChildPatientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereChildSex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereConsultationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereDangerSignsBaby($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereDangerSignsMother($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereDeliveryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereDeliveryTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereIronCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereIronDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereModeOfDelivery($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord wherePatientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord wherePlaceDelivered($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord wherePostpartum14dDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord wherePostpartum24hDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord wherePostpartum28dDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord wherePostpartum7dDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord wherePregnancyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord wherePregnancyOutcome($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord wherePrenatalVisitsCompleted($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereRecordedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostnatalRecord whereVitaminADate($value)
 *
 * @mixin \Eloquent
 */
class PostnatalRecord extends Model
{
    use LogsActivity;

    public const string OUTCOME_LIVE_BIRTH = 'live_birth';

    public const string OUTCOME_STILLBIRTH = 'stillbirth';

    public const string OUTCOME_ABORTION = 'abortion';

    public const string OUTCOME_OTHERS = 'others';

    public const array OUTCOMES = [
        self::OUTCOME_LIVE_BIRTH => 'Live Birth',
        self::OUTCOME_STILLBIRTH => 'Stillbirth',
        self::OUTCOME_ABORTION => 'Abortion',
        self::OUTCOME_OTHERS => 'Others',
    ];

    public const array PLACES = [
        'home' => 'Home',
        'health_center' => 'Health Center',
        'hospital' => 'Hospital',
        'other_facility' => 'Other Facility',
    ];

    public const array MODES = [
        'normal_vaginal' => 'Normal Vaginal',
        'cesarean' => 'Cesarean Section',
        'vacuum_forceps' => 'Vacuum / Forceps Assisted',
        'others' => 'Others',
    ];

    public const array ATTENDANTS = [
        'midwife' => 'Midwife',
        'physician' => 'Physician',
        'nurse' => 'Nurse',
        'traditional_birth_attendant' => 'Traditional Birth Attendant',
        'others' => 'Others',
    ];

    public const array DANGER_SIGNS_MOTHER = [
        'Vaginal bleeding',
        'Fever',
        'Severe headache',
        'Blurred vision',
        'Foul-smelling discharge',
        'Breast engorgement/abscess',
        'Lower abdominal pain',
        'Difficulty urinating',
        'Swollen legs/hands',
        'Others',
    ];

    public const array DANGER_SIGNS_BABY = [
        'Poor feeding',
        'Fever',
        'Hypothermia',
        'Fast/difficult breathing',
        'Jaundice',
        'Umbilical redness/discharge',
        'Convulsions',
        'Diarrhea',
        'Not passing urine/stool',
        'Others',
    ];

    public const array POSTPARTUM_SLOTS = [
        'postpartum_24h_date' => 1,
        'postpartum_7d_date' => 7,
        'postpartum_14d_date' => 14,
        'postpartum_28d_date' => 28,
    ];

    /**
     * Fields that only make sense when the outcome is a live birth.
     */
    public const array NEWBORN_FIELDS = [
        'child_last_name',
        'child_first_name',
        'child_middle_name',
        'child_sex',
        'child_birth_length_cm',
        'child_birth_weight_kg',
        'child_patient_id',
    ];

    protected $fillable = [
        'patient_id',
        'pregnancy_id',
        'consultation_id',
        'pregnancy_outcome',
        'prenatal_visits_completed',
        'place_delivered',
        'mode_of_delivery',
        'attendant_at_birth',
        'delivery_date',
        'delivery_time',
        'breastfeeding_date',
        'breastfeeding_time',
        'postpartum_24h_date',
        'postpartum_7d_date',
        'postpartum_14d_date',
        'postpartum_28d_date',
        'danger_signs_mother',
        'danger_signs_baby',
        'vitamin_a_date',
        'iron_date',
        'iron_count',
        'child_last_name',
        'child_first_name',
        'child_middle_name',
        'child_sex',
        'child_birth_length_cm',
        'child_birth_weight_kg',
        'child_patient_id',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'delivery_date' => 'date',
            'breastfeeding_date' => 'date',
            'postpartum_24h_date' => 'date',
            'postpartum_7d_date' => 'date',
            'postpartum_14d_date' => 'date',
            'postpartum_28d_date' => 'date',
            'danger_signs_mother' => 'array',
            'danger_signs_baby' => 'array',
            'vitamin_a_date' => 'date',
            'iron_date' => 'date',
            'child_birth_length_cm' => 'decimal:1',
            'child_birth_weight_kg' => 'decimal:2',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function pregnancy(): BelongsTo
    {
        return $this->belongsTo(Pregnancy::class);
    }

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }

    public function childPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'child_patient_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(HealthWorker::class, 'recorded_by');
    }
}
