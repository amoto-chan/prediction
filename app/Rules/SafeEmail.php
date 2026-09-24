<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects control characters before Laravel's email rule is applied.
 *
 * This is deliberately kept as an application rule because the project is
 * pinned to Laravel 11, whose current email validation release has an
 * upstream CRLF advisory. Addresses are also validated with email:rfc at
 * every boundary that accepts user input.
 */
class SafeEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            $fail('The :attribute must not contain control characters.');

            return;
        }

        if (trim($value) !== $value) {
            $fail('The :attribute must not have leading or trailing whitespace.');
        }
    }
}

