<?php

namespace App\Http\Requests;

use App\Services\VitalsService;
use Illuminate\Foundation\Http\FormRequest;

class VitalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...VitalsService::rules(),
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
