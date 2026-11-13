<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts international phone numbers: optional +, digits, spaces and
 * punctuation. The digit count must be between 8 and 15 (E.164).
 */
final class InternationalPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $raw = trim((string)$value);
        if ($raw === '' || preg_match('/^[0-9\s\-\+\(\)\.]+$/', $raw) !== 1) {
            $fail('validation.phone')->translate();

            return;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        $length = strlen($digits);
        if ($length < 8 || $length > 15) {
            $fail('validation.phone')->translate();
        }
    }
}
