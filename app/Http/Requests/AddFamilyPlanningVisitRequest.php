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
            'mode_of_transaction' => ['required', 'string', 'max:255'],
            'nature_of_visit' => ['required', 'string', 'max:255'],
            'chief_complaint' => ['nullable', 'string', 'max:500'],
            'visit_date' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['required', 'string', 'in:'.implode(',', FamilyPlanningClient::METHODS)],
            'schedule_next_visit' => ['nullable', 'date'],
            ...VitalsService::rules(required: true),
        ];
    }
}
