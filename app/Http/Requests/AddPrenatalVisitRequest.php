<?php

namespace App\Http\Requests;

use App\Services\VitalsService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AddPrenatalVisitRequest extends FormRequest
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
            'mode_of_transaction' => ['required', 'string', 'max:255'],
            'nature_of_visit' => ['required', 'string', 'max:255'],
            'chief_complaint' => ['nullable', 'string', 'max:500'],
            'visit_date' => ['required', 'date'],
            'fundic_height_cm' => ['nullable', 'numeric', 'min:0', 'max:99.9'],
            'fetal_heart_tone_bpm' => ['nullable', 'integer', 'min:60', 'max:220'],
            'next_visit_date' => ['nullable', 'date'],
            ...VitalsService::rules(required: true),
        ];
    }
}
