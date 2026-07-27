<?php

namespace App\Http\Requests;

use App\Models\Patient;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePatientWithHouseholdRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isCreatingHousehold = (int) $this->input('create_new_household') === 1;

        return [
            // Household creation flag
            'create_new_household' => 'nullable|boolean',

            // Household selection or creation
            'household_id' => [
                $isCreatingHousehold ? 'nullable' : 'required',
                $isCreatingHousehold ? 'nullable' : 'exists:households,id',
            ],

            // New household fields (only if creating)
            'new_household_zone_id' => ['nullable', 'required_if:create_new_household,1', 'integer', 'exists:zones,id'],
            'new_household_family_name_head' => ['nullable', 'required_if:create_new_household,1', 'string', 'max:255'],
            'new_household_contact_number' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\-\s()]*$/'],

            // Patient data
            'first_name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-zA-Z\s\-\.]+$/'],
            'last_name' => ['required', 'string', 'min:2', 'max:50', 'regex:/^[a-zA-Z\s\-\.]+$/'],
            'middle_name' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z\s\-\.]+$/'],

            'sex' => 'required|in:Male,Female',
            'date_of_birth' => 'required|date|before:today',
            'birth_place' => 'nullable|string|max:255',

            'civil_status' => 'nullable|in:Single (Walang Asawa),Married (May Asawa),Annulled (Hiwalay),Widow/er (Balo),Separated (Hiwalay),Co-Habitation (Paninirahang magkasama)',
            'blood_type' => 'nullable|in:A+,A-,B+,B-,O+,O-,AB+,AB-',
            'educational_attainment' => 'nullable|string',
            'employment_status' => 'nullable|string|max:100',

            'mother_name' => ['required', 'string', 'max:255'],
            'spouse_name' => ['required', 'string', 'max:255'],
            'family_relationship' => ['required', 'in:'.implode(',', Patient::FAMILY_RELATIONSHIP_OPTIONS)],
            'is_philhealth_member' => ['required', 'in:y,n'],
            'philhealth_no' => ['nullable', 'string', 'max:20'],
            'membership_category' => ['nullable', 'in:'.implode(',', Patient::PHILHEALTH_MEMBERSHIP_CATEGORIES)],
            'status_type' => ['nullable', 'in:'.implode(',', Patient::PHILHEALTH_STATUS_TYPES)],
            'is_pcb_member' => ['required', 'in:y,n'],

            'suffix' => 'nullable|string|max:50',
            'has_4ps' => 'nullable|boolean',
            'has_nhts' => 'nullable|boolean',
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
            'household_id.required' => 'You must select an existing household or create a new one.',
            'new_household_zone_id.required_if' => 'Zone is required when creating a new household.',
            'new_household_family_name_head.required_if' => 'Family name (head) is required when creating a new household.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert checkbox values to expected payloads
        $createNew = $this->boolean('create_new_household') ? 1 : 0;
        $this->merge([
            'create_new_household' => $createNew,
            'has_4ps' => $this->boolean('has_4ps') ? 1 : 0,
            'has_nhts' => $this->boolean('has_nhts') ? 1 : 0,
            'is_philhealth_member' => in_array($this->input('is_philhealth_member'), ['y', 'yes', '1'], true) ? 'y' : 'n',
            'is_pcb_member' => in_array($this->input('is_pcb_member'), ['y', 'yes', '1'], true) ? 'y' : 'n',
        ]);

        if ($this->input('is_philhealth_member') !== 'y') {
            $this->merge([
                'philhealth_no' => null,
                'membership_category' => null,
                'status_type' => null,
            ]);
        }

        // Only set new household fields if creating new household
        if ($createNew === 1) {
            $this->merge([
                'new_household_zone_id' => $this->input('new_household_zone_id') ? (int) $this->input('new_household_zone_id') : null,
            ]);
        } else {
            $this->merge([
                'new_household_zone_id' => null,
                'new_household_family_name_head' => null,
                'new_household_contact_number' => null,
            ]);
        }
    }
}
