<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ReferralQueryService
{
    public static function paginateIndex(string $query = '', ?string $status = null, ?User $user = null): LengthAwarePaginator
    {
        $builder = DB::table('outward_referrals')
            ->join('consultations', 'outward_referrals.consultation_id', '=', 'consultations.id')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->join('health_workers', 'consultations.worker_id', '=', 'health_workers.id')
            ->select(
                'outward_referrals.*',
                'consultations.status as consultation_status',
                'consultations.created_at as consultation_created_at',
                'patients.first_name as patient_first_name',
                'patients.last_name as patient_last_name',
                'patients.id as patient_id',
                'health_workers.first_name as worker_first_name',
                'health_workers.last_name as worker_last_name'
            );

        if ($user !== null && $user->isZoneScoped()) {
            $builder->whereIn('patients.household_id', $user->accessibleHouseholdIds());
        }

        if ($query !== '') {
            $term = trim($query);
            $builder->where(function ($q) use ($term) {
                $q->where('patients.first_name', 'like', '%'.$term.'%')
                    ->orWhere('patients.last_name', 'like', '%'.$term.'%')
                    ->orWhere('outward_referrals.destination_facility', 'like', '%'.$term.'%')
                    ->orWhere('outward_referrals.pertinent_history', 'like', '%'.$term.'%')
                    ->orWhere('outward_referrals.specific_details', 'like', '%'.$term.'%');
            });
        }

        if ($status !== null) {
            $builder->where('outward_referrals.status', $status);
        }

        return $builder->orderByDesc('outward_referrals.created_at')->paginate(15)->withQueryString();
    }

    public static function statusCounts(?User $user = null): Collection
    {
        $query = DB::table('outward_referrals')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status');

        if ($user !== null && $user->isZoneScoped()) {
            $query->join('consultations', 'outward_referrals.consultation_id', '=', 'consultations.id')
                ->whereIn('consultations.patient_id', $user->accessiblePatientIds());
        }

        return $query->pluck('total', 'status');
    }

    public static function totals(?User $user = null): array
    {
        $total = DB::table('outward_referrals');
        $thisWeek = DB::table('outward_referrals')
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);

        if ($user !== null && $user->isZoneScoped()) {
            $total->join('consultations', 'outward_referrals.consultation_id', '=', 'consultations.id')
                ->whereIn('consultations.patient_id', $user->accessiblePatientIds());
            $thisWeek->join('consultations', 'outward_referrals.consultation_id', '=', 'consultations.id')
                ->whereIn('consultations.patient_id', $user->accessiblePatientIds());
        }

        return [
            'total' => (clone $total)->count(),
            'thisWeek' => (clone $thisWeek)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function printData(int $id, ?User $user = null): array
    {
        $query = DB::table('outward_referrals')
            ->join('consultations', 'outward_referrals.consultation_id', '=', 'consultations.id')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->leftJoin('health_workers', 'consultations.worker_id', '=', 'health_workers.id')
            ->where('outward_referrals.id', $id);

        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('patients.household_id', $user->accessibleHouseholdIds());
        }

        $referral = $query->select(
            'outward_referrals.*',
            'consultations.*',
            'patients.*',
            'patients.id as patient_record_id',
            'health_workers.first_name as worker_first_name',
            'health_workers.last_name as worker_last_name'
        )
            ->first();

        if (! $referral) {
            abort(404, 'Referral not found');
        }

        $vitals = DB::table('vitals')
            ->where('consultation_id', $referral->consultation_id)
            ->orderByDesc('id')
            ->first();

        $referredAt = Carbon::parse($referral->created_at);
        $age = $referral->date_of_birth ? Carbon::parse($referral->date_of_birth)->age : null;
        $attendingProvider = trim(($referral->worker_first_name ?? '').' '.($referral->worker_last_name ?? '')) ?: null;

        $patient = (object) [
            'id' => $referral->patient_record_id,
            'first_name' => $referral->first_name,
            'last_name' => $referral->last_name,
            'middle_name' => $referral->middle_name,
            'suffix' => $referral->suffix,
            'date_of_birth' => $referral->date_of_birth,
            'residential_address' => $referral->residential_address,
            'is_philhealth_member' => $referral->is_philhealth_member,
            'has_nhts' => $referral->has_nhts ?? false,
            'has_4ps' => $referral->has_4ps ?? false,
            'membership_category' => $referral->membership_category,
            'household_id' => $referral->household_id,
        ];

        return [
            'referral' => $referral,
            'patient' => $patient,
            'vitals' => $vitals,
            'age' => $age,
            'referredAt' => $referredAt,
            'attendingProvider' => $attendingProvider,
        ];
    }
}
