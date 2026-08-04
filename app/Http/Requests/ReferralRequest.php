<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'referred_to' => ['required', 'string', 'max:255'],
            'referral_reasons' => ['nullable', 'array'],
            'referral_reasons.*' => ['string'],
            'referral_reason_details' => ['nullable', 'string', 'max:1000'],
            'pertinent_history' => ['required', 'nullable', 'string'],
            'actions_taken' => ['nullable', 'string'],
        ];
    }
}
