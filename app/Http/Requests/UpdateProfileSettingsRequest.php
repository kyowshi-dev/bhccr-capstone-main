<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_timeout' => ['required', 'integer', 'min:5', 'max:2880'],
        ];
    }

    public function messages(): array
    {
        return [
            'session_timeout.required' => 'Session timeout is required.',
            'session_timeout.integer' => 'Session timeout must be a number.',
            'session_timeout.min' => 'Session timeout must be at least 5 minutes.',
            'session_timeout.max' => 'Session timeout must not exceed 48 hours.',
        ];
    }
}
