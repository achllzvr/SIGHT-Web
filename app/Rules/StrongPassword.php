<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Requires 8+ chars with upper, lower, digit, and symbol.
 */
class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password = (string) $value;

        if (strlen($password) < 8) {
            $fail('Password must be at least 8 characters.');
            return;
        }

        if (!preg_match('/[a-z]/', $password)) {
            $fail('Password must include at least one lowercase letter.');
            return;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $fail('Password must include at least one uppercase letter.');
            return;
        }

        if (!preg_match('/[0-9]/', $password)) {
            $fail('Password must include at least one number.');
            return;
        }

        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $fail('Password must include at least one symbol (for example !@#$).');
        }
    }
}
