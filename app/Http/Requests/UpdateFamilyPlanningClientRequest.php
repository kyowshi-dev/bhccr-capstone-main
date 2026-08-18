<?php

namespace App\Http\Requests;

use App\Models\FamilyPlanningClient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFamilyPlanningClientRequest extends FormRequest
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
            'type_of_client' => ['required', 'in:'.implode(',', array_keys(FamilyPlanningClient::TYPES))],
            'method' => ['required', 'string', 'in:'.implode(',', FamilyPlanningClient::METHODS)],
            'drop_out_reason' => ['nullable', 'required_if:type_of_client,drop_out', 'string', 'max:500'],
            'schedule_next_visit' => ['nullable', 'date', 'after_or_equal:today'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'schedule_next_visit.after_or_equal' => 'The next follow-up date cannot be in the past.',
        ];
    }
}
