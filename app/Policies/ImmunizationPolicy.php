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
}
