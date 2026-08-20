<?php

namespace App\Http\Requests;

use App\Enums\MedicationRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePrescriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'medicine_name' => ['required', 'string', 'max:255'],
            'dosage' => ['required', 'string', 'max:255'],
            'route' => ['required', 'string', Rule::in(MedicationRoute::values())],
            'frequency' => ['required', 'string', 'max:255'],
            'duration' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'instructions' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Reject adding a duplicate custom medicine to the same consultation.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $consultation = $this->route('consultation');
            $medicineName = trim((string) $this->input('medicine_name'));

            if ($consultation && $medicineName !== '') {
                $existingId = (int) $this->route('prescriptionId');

                $exists = DB::table('prescriptions')
                    ->where('consultation_id', $consultation->id)
                    ->where('id', '!=', $existingId)
                    ->whereRaw('LOWER(custom_medicine_name) = ?', [strtolower($medicineName)])
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'medicine_name',
                        'This medicine is already on the current prescription. Edit or delete the existing entry instead of duplicating it.'
                    );
                }
            }
        });
    }
}
