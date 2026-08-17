<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Username field validation rules.
             *
             * @validation required - The username field must be present and not empty
             * @validation string - The username must be a string data type
             *
             * Note: In Laravel validation, 'string' is a validation rule that checks
             * the input is a string type, not a database column type. The actual database
             * column type (varchar, text, etc.) is defined separately in migrations.
             * Using 'string' in validation is appropriate because it validates the
             * incoming request data before it's persisted to the database.
             */
            'username' => ['required', 'string'],
            'password' => ['required'],
        ];
    }
}
