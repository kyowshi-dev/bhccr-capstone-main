<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class PatientQueryService
{
    public static function paginateIndex(string $sort, string $dir, ?User $user = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Patient::query()
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->leftJoinSub(
                DB::table('consultations')
                    ->select('patient_id', DB::raw('MAX(created_at) as last_visit'))
                    ->groupBy('patient_id'),
                'latest_consultations',
                function ($join) {
                    $join->on('patients.id', '=', 'latest_consultations.patient_id');
                }
            )
            ->select(
                'patients.*',
                'households.family_name_head',
                'households.zone_id',
                'households.contact_number',
                'latest_consultations.last_visit'
            );

        if ($user !== null) {
            $user->scopeAccessiblePatients($query);
        }

        match ($sort) {
            'name' => $query
                ->orderBy('patients.last_name', $dir)
                ->orderBy('patients.first_name', $dir),
            'age' => $dir === 'asc'
                ? $query->orderByDesc('patients.date_of_birth')
                : $query->orderBy('patients.date_of_birth', 'asc'),
            'last_visit' => $query
                ->orderByRaw('latest_consultations.last_visit IS NULL ASC')
                ->orderBy('latest_consultations.last_visit', $dir),
            default => $query->orderBy('patients.created_at', $dir),
        };

        return $query->paginate($perPage)->withQueryString();
    }
}
