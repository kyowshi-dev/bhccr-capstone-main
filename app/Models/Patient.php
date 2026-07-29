<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

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

    protected $casts = [
        'date_of_birth' => 'date',
        'has_4ps' => 'boolean',
        'has_nhts' => 'boolean',
    ];
}
