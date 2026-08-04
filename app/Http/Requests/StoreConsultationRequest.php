<?php

namespace App\Http\Requests;

use App\Services\VitalsService;
use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'mode_of_transaction' => ['required', 'string', 'max:255'],
            'referred_from' => ['nullable', 'string', 'max:255'],
            'nature_of_visit' => ['required', 'string', 'max:255'],
            'purpose_of_visit' => ['required', 'string', 'max:255'],
            'chief_complaint' => ['nullable', 'string', 'max:1000'],
            ...VitalsService::rules(required: true),
            'refer_to_higher_facility' => ['nullable', 'boolean'],
            'referred_to' => ['required_if:refer_to_higher_facility,1', 'nullable', 'string', 'max:255'],
            'referral_reasons' => ['nullable', 'array'],
            'referral_reasons.*' => ['string'],
            'referral_reason_details' => ['nullable', 'string', 'max:1000'],
            'pertinent_history' => ['required_if:refer_to_higher_facility,1', 'nullable', 'string'],
            'actions_taken' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'temperature.required' => 'Temperature is required.',
            'temperature.min' => 'Temperature must be at least 30°C.',
            'temperature.max' => 'Temperature must not exceed 45°C.',
        ];
    }
}
