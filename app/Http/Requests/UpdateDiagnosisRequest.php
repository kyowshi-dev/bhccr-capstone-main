<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDiagnosisRequest extends FormRequest
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
            'diagnosis_name' => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
