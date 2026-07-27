<?php

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    /**
     * Determine whether the user can view any reports.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('reports');
    }

    /**
     * Determine whether the user can view morbidity report.
     */
    public function viewMorbidity(User $user): bool
    {
        return $user->hasPermission('reports');
    }

    /**
     * Determine whether the user can view consultation summary.
     */
    public function viewConsultationSummary(User $user): bool
    {
        return $user->hasPermission('reports');
    }
}
