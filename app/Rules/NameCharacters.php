<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NameCharacters implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (preg_match('/^[\p{L}\p{M}][\p{L}\p{M}\s\-\.\']*$/u', (string) $value) !== 1) {
            $fail('The :attribute may only contain letters, spaces, hyphens, periods, and apostrophes.');
        }
    }
}
