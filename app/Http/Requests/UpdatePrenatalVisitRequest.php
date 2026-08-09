<?php

namespace App\Http\Requests;

use App\Models\PrenatalVisit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePrenatalVisitRequest extends FormRequest
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
            'visit_date' => ['required', 'date'],
            'fundic_height_cm' => ['nullable', 'numeric', 'min:0', 'max:99.9'],
            'fetal_heart_tone_bpm' => ['nullable', 'integer', 'min:60', 'max:220'],
            'next_visit_date' => ['nullable', 'date'],
            'consultation_id' => $this->consultationRule(),
        ];
    }

    /**
     * @return array<mixed>
     */
    private function consultationRule(): array
    {
        $rules = ['nullable', 'integer'];

        $visit = $this->route('visit');

        if ($visit instanceof PrenatalVisit) {
            $rules[] = Rule::exists('consultations', 'id')->where('patient_id', $visit->pregnancy->patient_id);
        }

        return $rules;
    }
}
