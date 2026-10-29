<?php

namespace Tests\Unit;

use App\Support\Jalali;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class JalaliTest extends TestCase
{
    public function test_converts_gregorian_dates_to_jalali_with_persian_digits(): void
    {
        $date = Carbon::create(2025, 11, 5, 12, 30, 0, 'Asia/Tehran');

        $this->assertSame('۱۴۰۴/۰۸/۱۴', Jalali::date($date));
        $this->assertSame('۱۴۰۴/۰۸/۱۴ ۱۲:۳۰', Jalali::dateTime($date));
        $this->assertSame('۱۴ آبان ۱۴۰۴', Jalali::date($date, 'j F Y'));
    }

    public function test_null_dates_render_as_empty_string(): void
    {
        $this->assertSame('', Jalali::date(null));
        $this->assertSame('', Jalali::dateTime(null));
    }

    public function test_converts_digits_both_ways(): void
    {
        $this->assertSame('BAR-۰۰۰۱۲۳', Jalali::toPersianDigits('BAR-000123'));
        $this->assertSame('۲۰۲۶', Jalali::toPersianDigits(2026));
        $this->assertSame('09120000001', Jalali::toLatinDigits('۰۹۱۲۰۰۰۰۰۰۱'));
        $this->assertSame('123', Jalali::toLatinDigits('١٢٣'));
    }

    public function test_formats_toman_amounts(): void
    {
        $this->assertSame('۲۸٬۵۰۰٬۰۰۰ تومان', Jalali::formatToman(28500000));
        $this->assertSame('۰ تومان', Jalali::formatToman(0));
        $this->assertSame('', Jalali::formatToman(null));
    }
}
