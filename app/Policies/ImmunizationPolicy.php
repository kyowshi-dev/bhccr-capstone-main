<?php

namespace App\Policies;

use App\Models\User;

class ImmunizationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('immunizations');
    }

    /**
     * Determine whether the user can view patient immunizations.
     */
    public function viewPatient(User $user): bool
    {
        return $user->hasPermission('immunizations');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('immunizations');
    }

    /**
     * Determine whether the user can enroll infants.
     */
    public function enrollInfant(User $user): bool
    {
        return $user->hasPermission('immunizations');
    }

    /**
     * Determine whether the user can mark or clear no-shows.
     */
    public function markNoShow(User $user): bool
    {
        return $user->hasPermission('immunizations');
    }

    /**
     * Determine whether the user can match households for infant enrollment.
     */
    public function householdMatch(User $user): bool
    {
        return $user->hasPermission('immunizations');
    }
}
