<?php

namespace App\Rules;

use Closure;
use DateTimeImmutable;
use Illuminate\Contracts\Validation\ValidationRule;

class Iso8601Timestamp implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})(?:\.(\d{1,6}))?(Z|[+-]\d{2}:\d{2})$/', $value, $matches)) {
            $fail('The :attribute must be an ISO 8601 timestamp with a timezone.');

            return;
        }
        $normalized = $matches[1].'.'.str_pad($matches[2], 6, '0').($matches[3] === 'Z' ? '+00:00' : $matches[3]);
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s.uP', $normalized);
        $errors = DateTimeImmutable::getLastErrors();
        if (! $parsed || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $parsed->format('Y-m-d\TH:i:s.uP') !== $normalized) {
            $fail('The :attribute must be a valid ISO 8601 timestamp.');
        }
    }
}
