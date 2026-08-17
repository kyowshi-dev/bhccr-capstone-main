<?php

namespace App\Http\Requests;

use App\Rules\CurrentPassword;
use App\Rules\PasswordPolicyRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', new CurrentPassword],
            'password' => ['required', 'string', new PasswordPolicyRule, 'confirmed'],
        ];
    }
}
