<?php

namespace App\Support;

use Carbon\Carbon;
use DateTimeInterface;
use Morilog\Jalali\Jalalian;

/**
 * Display helpers for the Persian calendar, Persian digits and Toman amounts.
 */
class Jalali
{
    public static function date(?DateTimeInterface $date, string $format = 'Y/m/d'): string
    {
        if ($date === null) {
            return '';
        }

        return self::toPersianDigits(Jalalian::fromCarbon(Carbon::instance($date))->format($format));
    }

    public static function dateTime(?DateTimeInterface $date, string $format = 'Y/m/d H:i'): string
    {
        return self::date($date, $format);
    }

    public static function toPersianDigits(string|int|float $value): string
    {
        return strtr((string) $value, [
            '0' => '۰',
            '1' => '۱',
            '2' => '۲',
            '3' => '۳',
            '4' => '۴',
            '5' => '۵',
            '6' => '۶',
            '7' => '۷',
            '8' => '۸',
            '9' => '۹',
        ]);
    }

    public static function toLatinDigits(string $value): string
    {
        return strtr($value, [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);
    }

    public static function number(int|float $value): string
    {
        return self::toPersianDigits(number_format($value, 0, '', '٬'));
    }

    public static function formatToman(?int $amount): string
    {
        if ($amount === null) {
            return '';
        }

        return self::number($amount).' تومان';
    }
}
