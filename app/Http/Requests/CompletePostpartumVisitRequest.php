<?php

namespace App\Http\Requests;

use App\Models\PostnatalRecord;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompletePostpartumVisitRequest extends FormRequest
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
            'slot' => ['required', 'in:postpartum_24h_date,postpartum_7d_date,postpartum_14d_date,postpartum_28d_date'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'consultation_id' => $this->consultationRule(),
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

    public function attributes(): array
    {
        return [
            'slot' => 'postpartum slot',
        ];
    }

    public function messages(): array
    {
        return [
            'date.before_or_equal' => 'The visit date cannot be in the future.',
        ];
    }
}
