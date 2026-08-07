<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RegisterPregnancyRequest extends FormRequest
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
            'gravidity' => ['required', 'integer', 'min:1', 'max:25'],
            'parity' => ['required', 'integer', 'min:0', 'max:25'],
            'term' => ['required', 'integer', 'min:0', 'max:25'],
            'preterm' => ['required', 'integer', 'min:0', 'max:25'],
            'livebirth' => ['required', 'integer', 'min:0', 'max:25'],
            'abortion' => ['required', 'integer', 'min:0', 'max:25'],
            'lmp' => ['required', 'date', 'before_or_equal:today'],
            'edc' => ['nullable', 'date'],
            'aog_weeks' => ['nullable', 'integer', 'min:0', 'max:45'],
            'syphilis_result' => ['required', 'in:negative,positive'],
            'penicillin' => ['required', 'in:no,yes'],
            'tt_date' => ['nullable', 'date', 'before_or_equal:today'],
            'iron_taken' => ['nullable', 'boolean'],
            'others' => ['nullable', 'string', 'max:500'],
        ];
    }
}
