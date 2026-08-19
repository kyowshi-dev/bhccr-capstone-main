<?php

namespace App\Http\Requests;

use App\Models\Patient;
use App\Rules\NameCharacters;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreInfantWithHouseholdRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('immunizations');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isCreating = $this->boolean('create_household');

        return [
            // Household attach or create
            'household_id' => $isCreating ? ['nullable', 'integer', 'exists:households,id'] : ['required', 'integer', 'exists:households,id'],
            'create_household' => ['nullable', 'boolean'],
            'zone_id' => ['nullable', 'required_if:create_household,1', 'integer', 'exists:zones,id'],
            'family_name_head' => ['nullable', 'required_if:create_household,1', 'string', 'max:255', new NameCharacters],
            'contact_number' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\-\s()]*$/'],

            // Infant identity
            'first_name' => ['required', 'string', 'min:2', 'max:50', new NameCharacters],
            'last_name' => ['required', 'string', 'min:2', 'max:50', new NameCharacters],
            'middle_name' => ['nullable', 'string', 'max:50', new NameCharacters],
            'suffix' => ['nullable', 'string', 'max:50', new NameCharacters],
            'sex' => ['required', 'in:Male,Female'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'birth_weight' => ['required', 'numeric', 'between:0.1,10'],
            'mother_id' => ['nullable', 'integer', 'exists:patients,id'],
            'mother_name' => ['required', 'string', 'max:255', new NameCharacters],
            'father_name' => ['nullable', 'string', 'max:255', new NameCharacters],
            'guardian_name' => ['nullable', 'string', 'max:255', new NameCharacters],
        ];
    }

    /**
     * Get the validation error messages.
     */
    public function messages(): array
    {
        return [
            'first_name.name_characters' => 'First name cannot contain numbers or special symbols.',
            'last_name.name_characters' => 'Last name cannot contain numbers or special symbols.',
            'middle_name.name_characters' => 'Middle name cannot contain numbers or special symbols.',
            'date_of_birth.before' => 'Birth date cannot be in the future.',
            'zone_id.required_if' => 'Zone is required when creating a new household.',
            'family_name_head.required_if' => 'Family name (head) is required when creating a new household.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->boolean('create_household') && ! $this->filled('household_id')) {
                $validator->errors()->add('household_id', 'Select an existing household or create a new one.');
            }

            if ($this->filled('mother_id')) {
                $mother = Patient::find($this->input('mother_id'));

                if ($mother === null) {
                    $validator->errors()->add('mother_id', 'The selected mother does not exist.');
                } elseif ($mother->sex !== 'Female') {
                    $validator->errors()->add('mother_id', 'The linked mother must be a female patient.');
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'create_household' => $this->boolean('create_household') ? 1 : 0,
        ]);
    }
}
