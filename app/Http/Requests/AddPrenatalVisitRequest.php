<?php

namespace App\Http\Requests;

use App\Models\Pregnancy;
use App\Services\VitalsService;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'mode_of_transaction' => ['required', 'string', 'in:Walk-in,Visited,Referral'],
            'nature_of_visit' => ['required', 'string', 'in:New Consultation/Case,Follow-up Visit'],
            'chief_complaint' => ['nullable', 'string', 'max:500'],
            'visit_date' => ['required', 'date', 'before_or_equal:today'],
            'fundic_height_cm' => ['nullable', 'numeric', 'min:0', 'max:99.9'],
            'fetal_heart_tone_bpm' => ['nullable', 'integer', 'min:60', 'max:220'],
            'next_visit_date' => ['nullable', 'date', 'after:visit_date'],
            ...VitalsService::rules(required: true),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $pregnancy = $this->route('pregnancy');

        if (! $pregnancy instanceof Pregnancy) {
            return;
        }

        $validator->after(function (Validator $validator) use ($pregnancy): void {
            if ($validator->errors()->has('visit_date') || empty($pregnancy->lmp)) {
                return;
            }

            $visitDate = Carbon::parse($this->input('visit_date'))->startOfDay();

            if ($visitDate->lt(Carbon::parse($pregnancy->lmp)->startOfDay())) {
                $validator->errors()->add('visit_date', 'The visit date cannot be before the LMP.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'visit_date.before_or_equal' => 'The visit date cannot be in the future.',
            'next_visit_date.after' => 'The next visit date must be after the visit date.',
        ];
    }
}
