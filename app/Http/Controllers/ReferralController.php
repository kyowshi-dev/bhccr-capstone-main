<?php

namespace App\Http\Controllers;

use App\Models\OutwardReferral;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReferralController extends Controller
{
    public function index(Request $request)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $query = DB::table('outward_referrals')
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

        if ($request->filled('query')) {
            $term = trim($request->input('query'));
            $query->where(function ($builder) use ($term) {
                $builder->where('patients.first_name', 'like', '%'.$term.'%')
                    ->orWhere('patients.last_name', 'like', '%'.$term.'%')
                    ->orWhere('outward_referrals.destination_facility', 'like', '%'.$term.'%')
                    ->orWhere('outward_referrals.pertinent_history', 'like', '%'.$term.'%')
                    ->orWhere('outward_referrals.specific_details', 'like', '%'.$term.'%');
            });
        }

        if ($request->filled('status') && in_array($request->input('status'), OutwardReferral::STATUSES, true)) {
            $query->where('outward_referrals.status', $request->input('status'));
        }

        $referrals = $query->orderByDesc('outward_referrals.created_at')->paginate(15)->withQueryString();

        $statusCounts = DB::table('outward_referrals')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalReferrals = DB::table('outward_referrals')->count();
        $thisWeekReferrals = DB::table('outward_referrals')
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        return view('referrals.index', [
            'referrals' => $referrals,
            'totalReferrals' => $totalReferrals,
            'thisWeekReferrals' => $thisWeekReferrals,
            'statusCounts' => $statusCounts,
            'statusLabels' => OutwardReferral::STATUS_LABELS,
            'statusOptions' => OutwardReferral::STATUSES,
        ]);
    }

    public function print(int $id)
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        return view('referrals.print', $this->resolvePrintData($id));
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        if (! auth()->user()->hasPermission('consultations')) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(OutwardReferral::STATUSES)],
        ]);

        $updated = DB::table('outward_referrals')
            ->where('id', $id)
            ->update([
                'status' => $validated['status'],
                'updated_at' => now(),
            ]);

        if (! $updated) {
            abort(404, 'Referral not found');
        }

        return redirect()
            ->back()
            ->with('success', 'Referral status updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function resolvePrintData(int $id): array
    {
        $referral = DB::table('outward_referrals')
            ->join('consultations', 'outward_referrals.consultation_id', '=', 'consultations.id')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->leftJoin('health_workers', 'consultations.worker_id', '=', 'health_workers.id')
            ->where('outward_referrals.id', $id)
            ->select(
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
