<?php

namespace App\Http\Requests;

use App\Models\PostnatalRecord;
use Illuminate\Validation\Rule;

class UpdatePostnatalRequest extends PostnatalFormRequest
{
    /**
     * @return array<mixed>
     */
    #[\Override]
    protected function consultationRule(): array
    {
        $rules = ['nullable', 'integer'];

        $record = $this->route('postnatal');

        if ($record instanceof PostnatalRecord) {
            $rules[] = Rule::exists('consultations', 'id')->where('patient_id', $record->patient_id);
        }

        return $rules;
    }
}
