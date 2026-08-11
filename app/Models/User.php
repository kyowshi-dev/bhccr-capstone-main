<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\LogsActivity;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $username
 * @property string $password
 * @property string|null $remember_token
 * @property bool $is_active
 * @property int|null $role_id
 * @property string|null $profile_photo_path
 * @property string|null $bio
 * @property string|null $email
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read HealthWorker|null $healthWorker
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read mixed $permissions
 * @property-read Role|null $role
 *
 * @method static Builder<static>|User accessibleConsultations()
 * @method static Builder<static>|User accessibleHouseholds()
 * @method static Builder<static>|User accessiblePatients()
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User query()
 * @method static Builder<static>|User whereBio($value)
 * @method static Builder<static>|User whereCreatedAt($value)
 * @method static Builder<static>|User whereEmail($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User whereIsActive($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User whereProfilePhotoPath($value)
 * @method static Builder<static>|User whereRememberToken($value)
 * @method static Builder<static>|User whereRoleId($value)
 * @method static Builder<static>|User whereUpdatedAt($value)
 * @method static Builder<static>|User whereUsername($value)
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'is_active',
        'profile_photo_path',
        'bio',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function permissions(): Attribute
    {
        return Attribute::make(
            get: function () {
                $role = $this->role;

                return $role ? $role->permissions : collect();
            },
        );
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function healthWorker(): HasOne
    {
        return $this->hasOne(HealthWorker::class);
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getPermissionNames(), true);
    }

    public function isAdmin(): bool
    {
        return $this->hasPermission('users');
    }

    public function canPrintHandout(): bool
    {
        return $this->hasPermission('print_handouts');
    }

    public function canViewDashboardHandouts(): bool
    {
        return $this->hasPermission('dashboard_handouts');
    }

    private function getPermissionNames(): array
    {
        return Cache::remember("user_permissions_{$this->id}", 3600, function () {
            return $this->permissions->pluck('name')->toArray();
        });
    }

    // ------------------------------------------------------------------
    // Zone-based data scoping (least privilege)
    //
    // Facility-level staff (Admin, Doctor, Nurse, Midwife) see all
    // records. Zone-assigned workers (BHW) holding the `household`
    // permission are restricted to patients, consultations, households
    // and reports within the zones assigned to their health worker
    // profile. `household` implies zone-level duty; `users` implies
    // administrator (unrestricted).
    // ------------------------------------------------------------------

    public function isZoneScoped(): bool
    {
        return $this->hasPermission('household') && ! $this->isAdmin();
    }

    /**
     * @return list<int>
     */
    public function accessibleZoneIds(): array
    {
        return $this->healthWorker?->zones()->pluck('zones.id')->all() ?? [];
    }

    /**
     * @return list<int>
     */
    public function accessibleHouseholdIds(): array
    {
        return Household::query()
            ->whereIn('zone_id', $this->accessibleZoneIds())
            ->pluck('id')
            ->all();
    }

    /**
     * @return list<int>
     */
    public function accessiblePatientIds(): array
    {
        return Patient::query()
            ->whereIn('household_id', $this->accessibleHouseholdIds())
            ->pluck('id')
            ->all();
    }

    public function canAccessPatient(Patient $patient): bool
    {
        return ! $this->isZoneScoped()
            || in_array($patient->household_id, $this->accessibleHouseholdIds(), true);
    }

    public function canAccessConsultation(Consultation $consultation): bool
    {
        return ! $this->isZoneScoped()
            || in_array($consultation->patient_id, $this->accessiblePatientIds(), true);
    }

    public function canAccessHousehold(int $householdId): bool
    {
        return ! $this->isZoneScoped()
            || in_array($householdId, $this->accessibleHouseholdIds(), true);
    }

    /**
     * Restrict an Eloquent Patient query to this user's assigned zones.
     */
    public function scopeAccessiblePatients(Builder $query): Builder
    {
        if (! $this->isZoneScoped()) {
            return $query;
        }

        return $query->whereIn('patients.household_id', $this->accessibleHouseholdIds());
    }

    /**
     * Restrict an Eloquent Consultation query to this user's assigned zones.
     */
    public function scopeAccessibleConsultations(Builder $query): Builder
    {
        if (! $this->isZoneScoped()) {
            return $query;
        }

        return $query->whereIn('consultations.patient_id', $this->accessiblePatientIds());
    }

    /**
     * Restrict an Eloquent Household query to this user's assigned zones.
     */
    public function scopeAccessibleHouseholds(Builder $query): Builder
    {
        if (! $this->isZoneScoped()) {
            return $query;
        }

        return $query->whereIn('households.zone_id', $this->accessibleZoneIds());
    }
}
