<?php

namespace App\Http\Requests;

use App\Models\Patient;
use Illuminate\Validation\Rule;

class StorePostnatalRequest extends PostnatalFormRequest
{
    #[\Override]
    protected function deliveryDateRules(): array
    {
        return ['required', 'date', 'before_or_equal:today'];
    }

    #[\Override]
    protected function breastfeedingDateRules(): array
    {
        return ['required', 'date', 'before_or_equal:today'];
    }

    /**
     * @return array<mixed>
     */
    #[\Override]
    protected function consultationRule(): array
    {
        $rules = ['nullable', 'integer'];

        $patient = $this->route('patient');

        if ($patient instanceof Patient) {
            $rules[] = Rule::exists('consultations', 'id')->where('patient_id', $patient->id);
        }

        return $rules;
    }
}
