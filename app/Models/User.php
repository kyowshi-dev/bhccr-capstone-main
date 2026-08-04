<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\LogsActivity;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

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
        ];
    }

    public function permissions(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->role?->permissions ?? collect(),
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

    public function canViewDashboardHandouts(string $context): bool
    {
        return match ($context) {
            'bhw' => $this->hasPermission('dashboard_handouts_bhw'),
            'clinical' => $this->hasPermission('dashboard_handouts_clinical'),
            'midwife' => $this->hasPermission('dashboard_handouts_midwife'),
            'admin' => $this->hasPermission('dashboard_handouts_admin'),
            default => false,
        };
    }

    private function getPermissionNames(): array
    {
        return Cache::remember("user_permissions_{$this->id}", 3600, function () {
            return $this->permissions->pluck('name')->toArray();
        });
    }
}
