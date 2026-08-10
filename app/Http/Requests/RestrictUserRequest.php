<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestrictUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password'],
        ];
    }
}
