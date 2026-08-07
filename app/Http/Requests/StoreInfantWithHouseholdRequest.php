<?php

namespace App\Http\Requests;

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
            'family_name_head' => ['nullable', 'required_if:create_household,1', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\-\s()]*$/'],

            // Infant identity
            'first_name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-zA-Z\s\-\.]+$/'],
            'last_name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-zA-Z\s\-\.]+$/'],
            'middle_name' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z\s\-\.]+$/'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'sex' => ['required', 'in:Male,Female'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'birth_weight' => ['nullable', 'numeric', 'between:0.1,10'],
            'mother_name' => ['nullable', 'string', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get the validation error messages.
     */
    public function messages(): array
    {
        return [
            'first_name.regex' => 'First name cannot contain numbers or special symbols.',
            'last_name.regex' => 'Last name cannot contain numbers or special symbols.',
            'middle_name.regex' => 'Middle name cannot contain numbers or special symbols.',
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
