<?php

namespace App\Models;

use App\Casts\EncryptedString;
use App\Helpers\PatientCode;
use App\Services\ChildImmunizationService;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $household_id
 * @property string $last_name
 * @property string $first_name
 * @property string|null $middle_name
 * @property string|null $suffix
 * @property string $sex
 * @property Carbon $date_of_birth
 * @property numeric|null $birth_weight
 * @property string|null $birth_place
 * @property string|null $blood_type
 * @property string $civil_status
 * @property string|null $educational_attainment
 * @property string|null $employment_status
 * @property bool $has_4ps
 * @property bool $has_nhts
 * @property-read string $initials
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property string $mother_name
 * @property string|null $guardian_name
 * @property string $spouse_name
 * @property string $family_relationship
 * @property string $residential_address
 * @property string $is_philhealth_member
 * @property string|null $status_type
 * @property mixed|null $philhealth_no
 * @property string|null $membership_category
 * @property string $is_pcb_member
 * @property-read Pregnancy|null $activePregnancy
 * @property-read mixed $age
 * @property-read mixed $age_detail
 * @property-read Collection<int, Consultation> $consultations
 * @property-read int|null $consultations_count
 * @property-read mixed $contact_number
 * @property-read FamilyPlanningClient|null $fpClient
 * @property-read Collection<int, FamilyPlanningClient> $fpClients
 * @property-read int|null $fp_clients_count
 * @property-read Household $household
 * @property-read Collection<int, Immunization> $immunizationRecords
 * @property-read int|null $immunization_records_count
 * @property-read Collection<int, ImmunizationStatusEvent> $immunizationStatusEvents
 * @property-read int|null $immunization_status_events_count
 * @property-read MaternalProfile|null $maternalProfile
 * @property-read mixed $patient_code
 * @property-read Collection<int, PostnatalRecord> $postnatalRecords
 * @property-read int|null $postnatal_records_count
 * @property-read Collection<int, Pregnancy> $pregnancies
 * @property-read int|null $pregnancies_count
 * @property-read mixed $zone_id
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereBirthPlace($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereBirthWeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereBloodType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereCivilStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereDateOfBirth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereEducationalAttainment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereEmploymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereFamilyRelationship($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereGuardianName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereHas4ps($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereHasNhts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereHouseholdId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereIsPcbMember($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereIsPhilhealthMember($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereMembershipCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereMiddleName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereMotherName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient wherePhilhealthNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereResidentialAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereSex($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereSpouseName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereStatusType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereSuffix($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Patient whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Patient extends Model
{
    use LogsActivity;

    protected $fillable = [
        'household_id',
        'last_name',
        'first_name',
        'middle_name',
        'suffix',
        'sex',
        'date_of_birth',
        'birth_place',
        'blood_type',
        'civil_status',
        'educational_attainment',
        'employment_status',
        'mother_name',
        'spouse_name',
        'family_relationship',
        'residential_address',
        'birth_weight',
        'guardian_name',
        'is_philhealth_member',
        'status_type',
        'philhealth_no',
        'membership_category',
        'is_pcb_member',
        'has_4ps',
        'has_nhts',
        'is_immunization_enrolled',
    ];

    public const FAMILY_RELATIONSHIP_OPTIONS = ['Father', 'Son', 'Mother', 'Daughter', 'Others'];

    public const PHILHEALTH_MEMBERSHIP_CATEGORIES = ['FE - Private', 'FE - Government', 'IE', 'Others'];

    public const PHILHEALTH_STATUS_TYPES = ['Member', 'Dependent'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'birth_weight' => 'decimal:2',
            'has_4ps' => 'boolean',
            'has_nhts' => 'boolean',
            'is_immunization_enrolled' => 'boolean',
            // PhilHealth number is a national ID - encrypt at rest (RA 10173 / HIPAA).
            // EncryptedString tolerates legacy plaintext left by outdated DB imports.
            'philhealth_no' => EncryptedString::class,
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(Consultation::class, 'patient_id');
    }

    public function immunizationRecords(): HasMany
    {
        return $this->hasMany(Immunization::class, 'patient_id');
    }

    public function maternalProfile(): HasOne
    {
        return $this->hasOne(MaternalProfile::class);
    }

    public function pregnancies(): HasMany
    {
        return $this->hasMany(Pregnancy::class);
    }

    public function activePregnancy(): HasOne
    {
        return $this->hasOne(Pregnancy::class)
            ->ofMany(
                ['lmp' => 'max'],
                fn (Builder $query) => $query->where('status', Pregnancy::STATUS_ACTIVE),
            );
    }

    public function fpClient(): HasOne
    {
        return $this->hasOne(FamilyPlanningClient::class)->orderByDesc('id');
    }

    public function fpClients(): HasMany
    {
        return $this->hasMany(FamilyPlanningClient::class);
    }

    public function postnatalRecords(): HasMany
    {
        return $this->hasMany(PostnatalRecord::class);
    }

    public function immunizationStatusEvents(): HasMany
    {
        return $this->hasMany(ImmunizationStatusEvent::class, 'patient_id');
    }

    public function patientCode(): Attribute
    {
        return Attribute::make(
            get: fn () => PatientCode::format($this->id),
        );
    }

    public function initials(): Attribute
    {
        return Attribute::make(
            get: fn () => substr($this->first_name, 0, 1).substr($this->last_name, 0, 1),
        );
    }

    public function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date_of_birth->age,
        );
    }

    public function ageDetail(): Attribute
    {
        return Attribute::make(
            get: fn () => array_values(ChildImmunizationService::ageParts($this)),
        );
    }

    public function zoneId(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->household->zone_id,
        );
    }

    public function contactNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->household->contact_number,
        );
    }
}
