<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case PendingReview = 'pending_review';
    case OpenForOffers = 'open_for_offers';
    case Assigned = 'assigned';
    case Loaded = 'loaded';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'در انتظار بررسی',
            self::OpenForOffers => 'دریافت پیشنهاد',
            self::Assigned => 'تخصیص یافته',
            self::Loaded => 'بارگیری شده',
            self::InTransit => 'در مسیر',
            self::Delivered => 'تحویل شده',
            self::Cancelled => 'لغو شده',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PendingReview => 'warning',
            self::OpenForOffers => 'info',
            self::Assigned => 'primary',
            self::Loaded => 'primary',
            self::InTransit => 'info',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
        };
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PendingReview => [self::OpenForOffers, self::Cancelled],
            self::OpenForOffers => [self::Assigned, self::Cancelled],
            self::Assigned => [self::Loaded, self::Cancelled],
            self::Loaded => [self::InTransit],
            self::InTransit => [self::Delivered],
            self::Delivered, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }
}
