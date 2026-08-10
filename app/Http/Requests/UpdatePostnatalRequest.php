<?php

namespace App\Http\Requests;

use App\Models\PostnatalRecord;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostnatalRequest extends FormRequest
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
        $isLiveBirth = $this->input('pregnancy_outcome') === PostnatalRecord::OUTCOME_LIVE_BIRTH;

        $childName = $isLiveBirth ? ['required', 'string', 'max:255'] : ['prohibited'];
        $childOptional = $isLiveBirth ? ['nullable', 'string', 'max:255'] : ['prohibited'];
        $childSex = $isLiveBirth ? ['required', 'in:M,F'] : ['prohibited'];
        $childMeasure = $isLiveBirth ? ['nullable', 'numeric', 'min:0', 'max:99.9'] : ['prohibited'];
        $childWeight = $isLiveBirth ? ['nullable', 'numeric', 'min:0', 'max:20'] : ['prohibited'];

        return [
            'pregnancy_id' => ['nullable', 'integer', 'exists:pregnancies,id'],
            'consultation_id' => $this->consultationRule(),
            'pregnancy_outcome' => ['required', 'in:'.implode(',', array_keys(PostnatalRecord::OUTCOMES))],
            'prenatal_visits_completed' => ['nullable', 'integer', 'min:0', 'max:99'],
            'place_delivered' => ['required', 'in:'.implode(',', array_keys(PostnatalRecord::PLACES))],
            'mode_of_delivery' => ['required', 'in:'.implode(',', array_keys(PostnatalRecord::MODES))],
            'attendant_at_birth' => ['required', 'in:'.implode(',', array_keys(PostnatalRecord::ATTENDANTS))],
            'delivery_date' => ['required', 'date'],
            'delivery_time' => ['required', 'date_format:H:i'],
            'breastfeeding_date' => ['required', 'date'],
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
            'child_last_name' => $childName,
            'child_first_name' => $childName,
            'child_middle_name' => $childOptional,
            'child_sex' => $childSex,
            'child_birth_length_cm' => $childMeasure,
            'child_birth_weight_kg' => $childWeight,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'child_last_name.prohibited' => 'Newborn details apply to live births only.',
            'child_first_name.prohibited' => 'Newborn details apply to live births only.',
            'child_middle_name.prohibited' => 'Newborn details apply to live births only.',
            'child_sex.prohibited' => 'Newborn details apply to live births only.',
            'child_birth_length_cm.prohibited' => 'Newborn details apply to live births only.',
            'child_birth_weight_kg.prohibited' => 'Newborn details apply to live births only.',
        ];
    }

    /**
     * @return array<mixed>
     */
    private function consultationRule(): array
    {
        $rules = ['nullable', 'integer'];

        $record = $this->route('postnatal');

        if ($record instanceof PostnatalRecord) {
            $rules[] = Rule::exists('consultations', 'id')->where('patient_id', $record->patient_id);
        }

        return $rules;
    }
}
