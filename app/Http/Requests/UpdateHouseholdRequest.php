<?php

namespace App\Http\Requests;

use App\Rules\NameCharacters;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHouseholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'family_name_head' => ['required', 'string', 'max:255', new NameCharacters],
            'contact_number' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\-\s()]*$/'],
        ];
    }
}
