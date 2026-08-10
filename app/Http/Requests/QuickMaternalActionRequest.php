<?php

namespace App\Http\Requests;

use App\Models\FamilyPlanningClient;
use App\Services\VitalsService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class QuickMaternalActionRequest extends FormRequest
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
            'action' => ['required', 'string', 'in:register,log_prenatal_visit,log_postpartum,log_fp_visit'],
            'lmp' => ['required_if:action,register', 'nullable', 'date', 'before_or_equal:today'],
            'risk_flags' => ['nullable', 'array'],
            'risk_flags.*' => ['string', 'in:age_under_18,age_over_35,hypertension,diabetes,previous_csection,multiple_gestation,previous_stillbirth,others'],
            'visit_date' => ['required_unless:action,register', 'nullable', 'date', 'before_or_equal:today'],
            'mode_of_transaction' => ['required_unless:action,register', 'nullable', 'string', 'max:255'],
            'nature_of_visit' => ['required_unless:action,register', 'nullable', 'string', 'max:255'],
            'chief_complaint' => ['nullable', 'string', 'max:500'],
            'purpose_of_visit' => ['nullable', 'string', 'in:Prenatal,Postpartum,Family Planning'],
            ...$this->vitalsRules(),
            'fundic_height_cm' => ['nullable', 'numeric', 'min:0', 'max:99.9'],
            'fetal_heart_tone_bpm' => ['nullable', 'integer', 'min:60', 'max:220'],
            'next_visit_date' => ['nullable', 'date', 'after:today'],
            'method' => ['required_if:action,log_fp_visit', 'nullable', 'string', 'in:'.implode(',', FamilyPlanningClient::METHODS)],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private function vitalsRules(): array
    {
        $rules = VitalsService::rules(required: false);

        foreach ($rules as $field => &$fieldRules) {
            $fieldRules[0] = 'required_unless:action,register';
        }

        return $rules;
    }
}
