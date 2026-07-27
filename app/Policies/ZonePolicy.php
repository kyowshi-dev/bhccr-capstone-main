<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Zone;

class ZonePolicy
{
    /**
     * Determine whether the user can view any zones.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('zones');
    }

    /**
     * Determine whether the user can view the zone.
     */
    public function view(User $user, Zone $zone): bool
    {
        return $user->hasPermission('zones');
    }

    /**
     * Determine whether the user can create zones.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('zones');
    }

    /**
     * Determine whether the user can update the zone.
     */
    public function update(User $user, Zone $zone): bool
    {
        return $user->hasPermission('zones');
    }

    /**
     * Determine whether the user can delete the zone.
     */
    public function delete(User $user, Zone $zone): bool
    {
        return $user->hasPermission('zones');
    }

    /**
     * Determine whether the user can restore the zone.
     */
    public function restore(User $user, Zone $zone): bool
    {
        return $user->hasPermission('zones');
    }

    /**
     * Determine whether the user can permanently delete the zone.
     */
    public function forceDelete(User $user, Zone $zone): bool
    {
        return $user->hasPermission('zones');
    }
}
