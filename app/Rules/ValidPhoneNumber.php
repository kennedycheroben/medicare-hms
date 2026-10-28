<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ValidPhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('The :attribute must be a valid telephone contact.');

            return;
        }

        $cleaned = static::clean($value);

        // 1. Kenyan Mobile format: 07XXXXXXXX, 01XXXXXXXX (10 digits)
        if (preg_match('/^0[71]\d{8}$/', $cleaned)) {
            return;
        }

        // 2. Kenyan Mobile with country code without plus: 2547XXXXXXXX, 2541XXXXXXXX (12 digits)
        if (preg_match('/^254[71]\d{8}$/', $cleaned)) {
            return;
        }

        // 3. Kenyan Mobile with international plus: +2547XXXXXXXX, +2541XXXXXXXX
        if (preg_match('/^\+254[71]\d{8}$/', $cleaned)) {
            return;
        }

        // 4. Kenyan Landline: 020XXXXXXX or +25420XXXXXXX
        if (preg_match('/^(0|\+254)20\d{6,7}$/', $cleaned)) {
            return;
        }

        // 5. Standard International E.164 phone format: +[country_code][number] (8 to 15 digits)
        if (preg_match('/^\+[1-9]\d{7,14}$/', $cleaned)) {
            return;
        }

        $fail('Please provide a valid Kenyan phone number (e.g. +254 712 345 678 or 0712 345 678) or standard international format with country code.');
    }

    /**
     * Remove formatting spaces, hyphens, and parentheses from a phone string.
     */
    public static function clean(string $phone): string
    {
        // Preserve leading plus if present
        $hasPlus = str_starts_with(trim($phone), '+');
        $digits = preg_replace('/\D/', '', $phone);

        return $hasPlus ? "+{$digits}" : (string) $digits;
    }

    /**
     * Normalize a phone number to standard international E.164 format (+254...).
     */
    public static function normalize(string $phone): string
    {
        $cleaned = static::clean($phone);

        // Convert local Kenyan format 07XXXXXXXX or 01XXXXXXXX to +254...
        if (preg_match('/^0([71]\d{8})$/', $cleaned, $matches)) {
            return "+254{$matches[1]}";
        }

        // Convert 2547XXXXXXXX to +254...
        if (preg_match('/^254([71]\d{8})$/', $cleaned, $matches)) {
            return "+254{$matches[1]}";
        }

        // Convert 020XXXXXXX landline to +25420...
        if (preg_match('/^020(\d{6,7})$/', $cleaned, $matches)) {
            return "+25420{$matches[1]}";
        }

        // Return international formatted or cleaned number
        return $cleaned;
    }
}
