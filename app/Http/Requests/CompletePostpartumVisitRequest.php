<?php

namespace App\Http\Requests;

use App\Services\VitalsService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'mode_of_transaction' => ['required', 'string', 'max:255'],
            'nature_of_visit' => ['required', 'string', 'max:255'],
            'chief_complaint' => ['nullable', 'string', 'max:500'],
            ...VitalsService::rules(required: true),
        ];
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
