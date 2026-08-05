<?php

namespace App\Models;

use App\Helpers\PatientCode;
use App\Services\ChildImmunizationService;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function patientCode(): Attribute
    {
        return Attribute::make(
            get: fn () => PatientCode::format($this->id),
        );
    }

    public function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date_of_birth ? $this->date_of_birth->age : null,
        );
    }

    public function ageDetail(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->date_of_birth ? array_values(ChildImmunizationService::ageParts($this)) : null,
        );
    }

    public function zoneId(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->household?->zone_id,
        );
    }

    public function contactNumber(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->household?->contact_number,
        );
    }
}
