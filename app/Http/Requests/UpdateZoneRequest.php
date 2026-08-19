<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'zone_number' => ['required', 'string', 'max:255', 'regex:/^[\p{L}\p{M}\d\s\-\.#]+$/u', Rule::unique('zones', 'zone_number')->ignore($id)],
            'assigned_worker_id' => ['nullable', 'exists:health_workers,id'],
        ];
    }
}
