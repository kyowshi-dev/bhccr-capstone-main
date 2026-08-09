<?php

namespace App\Http\Requests;

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
            'purpose_of_visit' => ['nullable', 'string', 'in:Prenatal,Postpartum,Family Planning'],
            ...VitalsService::rules(required: false),
            'fundic_height_cm' => ['nullable', 'numeric', 'min:0', 'max:99.9'],
            'fetal_heart_tone_bpm' => ['nullable', 'integer', 'min:60', 'max:220'],
            'next_visit_date' => ['nullable', 'date', 'after:today'],
            'method' => ['required_if:action,log_fp_visit', 'nullable', 'string'],
        ];
    }
}
