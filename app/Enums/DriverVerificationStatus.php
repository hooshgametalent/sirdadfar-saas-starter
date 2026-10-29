<?php

namespace App\Enums;

enum DriverVerificationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار تأیید',
            self::Approved => 'تأیید شده',
            self::Rejected => 'رد شده',
        };
    }
}
