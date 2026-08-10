<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'zone_number' => ['required', 'string', 'max:255', 'unique:zones,zone_number'],
            'assigned_worker_id' => ['nullable', 'exists:health_workers,id'],
        ];
    }
}
