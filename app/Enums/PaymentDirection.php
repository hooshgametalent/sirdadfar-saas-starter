<?php

namespace App\Enums;

enum PaymentDirection: string
{
    case FromShipper = 'from_shipper';
    case ToDriver = 'to_driver';

    public function label(): string
    {
        return match ($this) {
            self::FromShipper => 'دریافت از صاحب بار',
            self::ToDriver => 'پرداخت به راننده',
        };
    }
}
