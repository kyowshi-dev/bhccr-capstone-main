<?php

namespace App\Rules;

use App\Models\ApplicationSetting;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordPolicyRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $minLength = (int) ApplicationSetting::get('password_min_length', 8);

        if (strlen((string) $value) < $minLength) {
            $fail("The password must be at least {$minLength} characters.");

            return;
        }

        if ($this->enabled('password_require_uppercase') && preg_match('/[A-Z]/', (string) $value) !== 1) {
            $fail('The password must contain at least one uppercase letter.');

            return;
        }

        if ($this->enabled('password_require_number') && preg_match('/[0-9]/', (string) $value) !== 1) {
            $fail('The password must contain at least one number.');

            return;
        }

        if ($this->enabled('password_require_symbol') && preg_match('/[^A-Za-z0-9]/', (string) $value) !== 1) {
            $fail('The password must contain at least one symbol.');
        }
    }

    private function enabled(string $key): bool
    {
        return ApplicationSetting::get($key, '0') === '1';
    }
}
