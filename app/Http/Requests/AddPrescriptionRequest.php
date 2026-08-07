<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddPrescriptionRequest extends FormRequest
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
            'medicine_id' => ['required', 'integer', 'exists:medicines_lookup,id'],
            'dosage' => ['required', 'string', 'max:255'],
            'frequency' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
