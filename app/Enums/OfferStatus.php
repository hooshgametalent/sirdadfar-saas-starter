<?php

namespace App\Enums;

enum OfferStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار پاسخ',
            self::Accepted => 'پذیرفته شده',
            self::Rejected => 'رد شده',
            self::Withdrawn => 'پس گرفته شده',
        };
    }
}
