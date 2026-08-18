<?php

namespace App\Http\Requests;

use App\Models\PostnatalRecord;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class PostnatalFormRequest extends FormRequest
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
            'pregnancy_outcome' => ['required', 'in:'.implode(',', array_keys(PostnatalRecord::OUTCOMES))],
            'prenatal_visits_completed' => ['nullable', 'integer', 'min:0', 'max:99'],
            'place_delivered' => ['required', 'in:'.implode(',', array_keys(PostnatalRecord::PLACES))],
            'mode_of_delivery' => ['required', 'in:'.implode(',', array_keys(PostnatalRecord::MODES))],
            'attendant_at_birth' => ['required', 'in:'.implode(',', array_keys(PostnatalRecord::ATTENDANTS))],
            'delivery_date' => $this->deliveryDateRules(),
            'delivery_time' => ['required', 'date_format:H:i'],
            'breastfeeding_date' => $this->breastfeedingDateRules(),
            'breastfeeding_time' => ['required', 'date_format:H:i'],
            'postpartum_24h_date' => ['nullable', 'date', 'after_or_equal:delivery_date'],
            'postpartum_7d_date' => ['nullable', 'date', 'after_or_equal:delivery_date'],
            'postpartum_14d_date' => ['nullable', 'date', 'after_or_equal:delivery_date'],
            'postpartum_28d_date' => ['nullable', 'date', 'after_or_equal:delivery_date'],
            'danger_signs_mother' => ['nullable', 'array'],
            'danger_signs_mother.*' => ['string', 'in:'.implode(',', PostnatalRecord::DANGER_SIGNS_MOTHER)],
            'danger_signs_baby' => ['nullable', 'array'],
            'danger_signs_baby.*' => ['string', 'in:'.implode(',', PostnatalRecord::DANGER_SIGNS_BABY)],
            'vitamin_a_date' => ['nullable', 'date', 'before_or_equal:today'],
            'iron_date' => ['nullable', 'date', 'before_or_equal:today'],
            'iron_count' => ['nullable', 'integer', 'min:0', 'max:999'],
            'child_last_name' => $childName,
            'child_first_name' => $childName,
            'child_middle_name' => $childOptional,
            'child_sex' => $childSex,
            'child_birth_length_cm' => $childMeasure,
            'child_birth_weight_kg' => $childWeight,
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['delivery_date', 'breastfeeding_date'])) {
                return;
            }

            $breastfeedingDate = Carbon::parse($this->input('breastfeeding_date'))->startOfDay();
            $deliveryDate = Carbon::parse($this->input('delivery_date'))->startOfDay();

            if ($breastfeedingDate->lt($deliveryDate)) {
                $validator->errors()->add('breastfeeding_date', 'The breastfeeding date cannot be before the delivery date.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function attributes(): array
    {
        return [
            'postpartum_24h_date' => '24-hour visit date',
            'postpartum_7d_date' => '7-day visit date',
            'postpartum_14d_date' => '14-day visit date',
            'postpartum_28d_date' => '28-day visit date',
        ];
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'child_last_name.prohibited' => 'Newborn details apply to live births only.',
            'child_first_name.prohibited' => 'Newborn details apply to live births only.',
            'child_middle_name.prohibited' => 'Newborn details apply to live births only.',
            'child_sex.prohibited' => 'Newborn details apply to live births only.',
            'child_birth_length_cm.prohibited' => 'Newborn details apply to live births only.',
            'child_birth_weight_kg.prohibited' => 'Newborn details apply to live births only.',
            'delivery_date.before_or_equal' => 'The delivery date cannot be in the future.',
            'breastfeeding_date.before_or_equal' => 'The breastfeeding date cannot be in the future.',
            'vitamin_a_date.before_or_equal' => 'The vitamin A date cannot be in the future.',
            'iron_date.before_or_equal' => 'The iron date cannot be in the future.',
        ];
    }

    /**
     * @return array<mixed>
     */
    protected function deliveryDateRules(): array
    {
        return ['required', 'date', 'before_or_equal:today'];
    }

    /**
     * @return array<mixed>
     */
    protected function breastfeedingDateRules(): array
    {
        return ['required', 'date', 'before_or_equal:today'];
    }
}
