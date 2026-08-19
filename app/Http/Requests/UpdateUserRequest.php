<?php

namespace App\Http\Requests;

use App\Rules\NameCharacters;
use App\Rules\PasswordPolicyRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        // Changing another user's role is a sensitive action: the admin must
        // re-confirm their own password. Self role changes are excluded because
        // the controller already blocks them.
        $roleChanged = $this->route('user')->id !== auth()->id()
            && $this->route('user')->role_id !== (int) $this->input('role_id');

        return [
            'first_name' => ['required', 'string', 'max:255', new NameCharacters],
            'last_name' => ['required', 'string', 'max:255', new NameCharacters],
            'contact_number' => ['nullable', 'string', 'max:255', 'regex:/^[0-9+\-\s()]*$/'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($userId)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', new PasswordPolicyRule, 'confirmed'],
            'role_id' => ['required', 'exists:user_roles,id'],
            'current_password' => [Rule::requiredIf($roleChanged), 'current_password'],
        ];
    }
}
