<?php

namespace App\Http\Requests;

use App\Models\PostnatalRecord;
use App\Services\VitalsService;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'slot' => ['required', 'in:'.implode(',', array_keys(PostnatalRecord::POSTPARTUM_SLOTS))],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'mode_of_transaction' => ['required', 'string', 'max:255'],
            'nature_of_visit' => ['required', 'string', 'max:255'],
            'chief_complaint' => ['nullable', 'string', 'max:500'],
            'consultation_id' => $this->consultationRule(),
            ...VitalsService::rules(required: true),
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

    public function withValidator(Validator $validator): void
    {
        $record = $this->route('postnatal');

        if (! $record instanceof PostnatalRecord) {
            return;
        }

        $validator->after(function (Validator $validator) use ($record): void {
            if ($validator->errors()->has('date')) {
                return;
            }

            $visitDate = Carbon::parse($this->input('date'))->startOfDay();

            if ($visitDate->lt(Carbon::parse($record->delivery_date)->startOfDay())) {
                $validator->errors()->add('slot', 'The visit date cannot be before the delivery date.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'slot' => 'postpartum slot',
        ];
    }

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'date.before_or_equal' => 'The visit date cannot be in the future.',
        ];
    }
}
