<?php

namespace App\Http\Controllers;

use App\Models\OutwardReferral;
use App\Services\NotificationService;
use App\Services\ReferralQueryService;
use App\Services\ReferralService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReferralController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizePermission('consultations');

        $status = $request->filled('status') && in_array($request->input('status'), OutwardReferral::STATUSES, true)
            ? $request->input('status')
            : null;

        $referrals = ReferralQueryService::paginateIndex($request->input('query', ''), $status, auth()->user(), pageSize(15));
        $totals = ReferralQueryService::totals(auth()->user());

        return view('referrals.index', [
            'referrals' => $referrals,
            'totalReferrals' => $totals['total'],
            'thisWeekReferrals' => $totals['thisWeek'],
            'statusCounts' => ReferralQueryService::statusCounts(auth()->user()),
            'statusLabels' => OutwardReferral::STATUS_LABELS,
            'statusOptions' => OutwardReferral::STATUSES,
        ]);
    }

    public function print(int $id): View
    {
        $this->authorizePermission('consultations');

        return view('referrals.print', ReferralQueryService::printData($id, auth()->user()));
    }

    public function updateStatus(Request $request, int $id): RedirectResponse
    {
        $this->authorizePermission('consultations');

        $referral = OutwardReferral::findOrFail($id);

        if (! auth()->user()->canAccessConsultation($referral->consultation)) {
            abort(403, 'This referral is outside your assigned zones.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(OutwardReferral::STATUSES)],
        ]);

        if (! ReferralService::updateStatus($id, $validated['status'])) {
            abort(404, 'Referral not found');
        }

        $this->notifyStatusChange($referral, $validated['status']);

        return redirect()
            ->back()
            ->with('success', 'Referral status updated.');
    }

    /**
     * Alert consultation staff when a referral's status changes.
     */
    private function notifyStatusChange(OutwardReferral $referral, string $status): void
    {
        $consultation = $referral->consultation;
        $patient = $consultation->patient;
        $name = trim(fullName($patient->last_name ?? null, $patient->first_name ?? null, $patient->middle_name ?? null, $patient->suffix ?? null));
        $patientLabel = $name !== '' ? $name : 'Patient #'.$patient->id;
        $label = OutwardReferral::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status));

        NotificationService::sendToPermissionHolders(
            'consultations',
            'referral_status_changed',
            'Referral #'.$referral->id.' '.$label.' - '.$patientLabel,
            'The referral to '.$referral->destination_facility.' was marked '.strtolower($label).'.',
            route('consultations.show', $consultation->id),
            patientIds: [$patient->id],
            excludeUserId: auth()->id(),
        );
    }
}
