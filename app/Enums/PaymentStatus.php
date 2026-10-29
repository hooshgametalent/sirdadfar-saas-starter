<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار تسویه',
            self::Paid => 'تسویه شده',
            self::Cancelled => 'لغو شده',
        };
    }
}
