<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListPatientsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort' => ['sometimes', Rule::in(['name', 'age', 'last_visit', 'created'])],
            'dir' => ['sometimes', Rule::in(['asc', 'desc'])],
        ];
    }
}
