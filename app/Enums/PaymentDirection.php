<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentDirection: string implements HasLabel
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

    public function getLabel(): string
    {
        return $this->label();
    }
}
