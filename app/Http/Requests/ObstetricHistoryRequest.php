<?php

namespace App\Http\Requests;

use App\Models\MaternalProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ObstetricHistoryRequest extends FormRequest
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
            'menarche_age' => ['nullable', 'integer', 'min:0', 'max:99'],
            'period_duration_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'cycle_interval_days' => ['nullable', 'integer', 'min:1', 'max:120'],
            'onset_sexual_intercourse_age' => ['nullable', 'integer', 'min:0', 'max:99'],
            'birth_control_method' => ['nullable', 'string', 'in:'.implode(',', MaternalProfile::BIRTH_CONTROL_METHODS)],
            'menopause' => ['required', 'in:no,yes'],
        ];
    }
}
