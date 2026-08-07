<?php

namespace App\Http\Requests;

use App\Models\PostnatalRecord;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePostnatalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('maternal');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pregnancy_id' => ['nullable', 'integer', 'exists:pregnancies,id'],
            'pregnancy_outcome' => ['required', 'in:'.implode(',', array_keys(PostnatalRecord::OUTCOMES))],
            'prenatal_visits_completed' => ['nullable', 'integer', 'min:0', 'max:99'],
            'place_delivered' => ['required', 'in:'.implode(',', array_keys(PostnatalRecord::PLACES))],
            'mode_of_delivery' => ['required', 'in:'.implode(',', array_keys(PostnatalRecord::MODES))],
            'attendant_at_birth' => ['required', 'in:'.implode(',', array_keys(PostnatalRecord::ATTENDANTS))],
            'delivery_date' => ['required', 'date', 'before_or_equal:today'],
            'delivery_time' => ['required', 'date_format:H:i'],
            'breastfeeding_date' => ['required', 'date', 'before_or_equal:today'],
            'breastfeeding_time' => ['required', 'date_format:H:i'],
            'postpartum_24h_date' => ['nullable', 'date'],
            'postpartum_7d_date' => ['nullable', 'date'],
            'postpartum_14d_date' => ['nullable', 'date'],
            'postpartum_28d_date' => ['nullable', 'date'],
            'danger_signs_mother' => ['nullable', 'array'],
            'danger_signs_mother.*' => ['string', 'in:'.implode(',', PostnatalRecord::DANGER_SIGNS_MOTHER)],
            'danger_signs_baby' => ['nullable', 'array'],
            'danger_signs_baby.*' => ['string', 'in:'.implode(',', PostnatalRecord::DANGER_SIGNS_BABY)],
            'vitamin_a_date' => ['nullable', 'date'],
            'iron_date' => ['nullable', 'date'],
            'iron_count' => ['nullable', 'integer', 'min:0', 'max:999'],
            'child_last_name' => ['required', 'string', 'max:255'],
            'child_first_name' => ['required', 'string', 'max:255'],
            'child_middle_name' => ['nullable', 'string', 'max:255'],
            'child_sex' => ['required', 'in:M,F'],
            'child_birth_length_cm' => ['nullable', 'numeric', 'min:0', 'max:99.9'],
            'child_birth_weight_kg' => ['nullable', 'numeric', 'min:0', 'max:20'],
        ];
    }
}
