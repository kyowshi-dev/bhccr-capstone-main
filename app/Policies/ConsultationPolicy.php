<?php

namespace App\Policies;

use App\Models\User;

class ConsultationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('consultations');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): bool
    {
        return $user->hasPermission('consultations');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('consultations');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): bool
    {
        return $user->hasPermission('consultations');
    }

    /**
     * Determine whether the user can add diagnosis.
     */
    public function addDiagnosis(User $user): bool
    {
        return $user->hasPermission('consultations');
    }

    /**
     * Determine whether the user can add prescription.
     */
    public function addPrescription(User $user): bool
    {
        return $user->hasPermission('consultations');
    }
}
