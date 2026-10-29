<?php

namespace App\Support;

/**
 * Normalises Iranian mobile numbers typed with Persian, Arabic or Latin
 * digits, spaces, dashes or a +98 prefix into the 09xxxxxxxxx form.
 */
class Mobile
{
    public const PATTERN = '/^09\d{9}$/';

    public static function normalize(?string $value): string
    {
        $digits = preg_replace('/\D+/', '', Jalali::toLatinDigits((string) $value)) ?? '';

        if (str_starts_with($digits, '0098')) {
            $digits = '0'.substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        } elseif (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    public static function isValid(?string $value): bool
    {
        return preg_match(self::PATTERN, self::normalize($value)) === 1;
    }
}
