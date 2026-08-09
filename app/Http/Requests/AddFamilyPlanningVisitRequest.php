<?php

namespace App\Http\Requests;

use App\Models\FamilyPlanningClient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'visit_date' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['required', 'string', 'in:'.implode(',', FamilyPlanningClient::METHODS)],
            'schedule_next_visit' => ['nullable', 'date'],
            'consultation_id' => $this->consultationRule(),
        ];
    }

    /**
     * @return array<mixed>
     */
    private function consultationRule(): array
    {
        $rules = ['nullable', 'integer'];

        $client = $this->route('client');

        if ($client instanceof FamilyPlanningClient) {
            $rules[] = Rule::exists('consultations', 'id')->where('patient_id', $client->patient_id);
        }

        return $rules;
    }
}
