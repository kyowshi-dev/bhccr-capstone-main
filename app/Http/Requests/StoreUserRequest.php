<?php

namespace App\Http\Requests;

use App\Rules\NameCharacters;
use App\Rules\PasswordPolicyRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255', new NameCharacters],
            'last_name' => ['required', 'string', 'max:255', new NameCharacters],
            'contact_number' => ['nullable', 'string', 'max:255', 'regex:/^[0-9+\-\s()]*$/'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', new PasswordPolicyRule, 'confirmed'],
            'role_id' => ['required', 'exists:user_roles,id'],
        ];
    }
}
