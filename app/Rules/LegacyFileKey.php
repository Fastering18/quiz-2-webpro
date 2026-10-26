<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Legacy "key" string fields stay accepted for compatibility, but may not point into
 * the structured namespaces written by FileStorage (which are always 3+ segments deep),
 * so a client can't mint a download link to somebody else's file.
 */
class LegacyFileKey implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('#^[A-Za-z0-9_.-]{1,120}(/[A-Za-z0-9_.-]{1,120})?$#', $value) || str_contains($value, '..')) {
            $fail('Format key file tidak valid.');
        }
    }
}
