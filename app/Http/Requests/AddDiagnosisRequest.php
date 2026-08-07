<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddDiagnosisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'diagnosis_id' => ['required', 'integer', 'exists:diagnosis_lookup,id'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
