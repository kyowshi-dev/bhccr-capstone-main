<?php

namespace App\Http\Requests;

use App\Models\FamilyPlanningClient;
use App\Services\VitalsService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddFamilyPlanningVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('maternal');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mode_of_transaction' => ['required', 'string', 'in:Walk-in,Visited,Referral'],
            'nature_of_visit' => ['required', 'string', 'in:New Consultation/Case,Follow-up Visit'],
            'chief_complaint' => ['nullable', 'string', 'max:500'],
            'visit_date' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['required', 'string', 'in:'.implode(',', FamilyPlanningClient::METHODS)],
            'schedule_next_visit' => ['nullable', 'date', 'after:visit_date'],
            ...VitalsService::rules(required: true),
        ];
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'schedule_next_visit.after' => 'The next follow-up date must be after the visit date.',
        ];
    }
}
