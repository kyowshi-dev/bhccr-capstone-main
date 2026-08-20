<?php

namespace App\Http\Requests;

use App\Enums\MedicationRoute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AddPrescriptionRequest extends FormRequest
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
            'medicine_id' => ['required', 'integer', Rule::exists('medicines_lookup', 'id')->whereNull('deleted_at')],
            'dosage' => ['required', 'string', 'max:255'],
            'route' => ['required', 'string', Rule::in(MedicationRoute::values())],
            'frequency' => ['required', 'string', 'max:255'],
            'duration' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'instructions' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Reject prescribing the same medicine twice on one consultation.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $consultation = $this->route('consultation');
            $medicineId = (int) $this->input('medicine_id');

            if ($consultation && $medicineId > 0) {
                $exists = DB::table('prescriptions')
                    ->where('consultation_id', $consultation->id)
                    ->where('medicine_id', $medicineId)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'medicine_id',
                        'This medicine is already on the current prescription. Edit or delete the existing entry instead of duplicating it.'
                    );
                }
            }
        });
    }
}
