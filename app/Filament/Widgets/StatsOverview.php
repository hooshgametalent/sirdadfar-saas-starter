<?php

namespace App\Filament\Widgets;

use App\Enums\DriverVerificationStatus;
use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\Review;
use App\Support\Jalali;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return auth()->user()?->isStaff() ?? false;
    }

    protected function getStats(): array
    {
        // One grouped query for all order counters.
        $orders = Order::query()
            ->toBase()
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) AS today', [today()])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS open_for_offers', [OrderStatus::OpenForOffers->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS in_transit', [OrderStatus::InTransit->value])
            ->selectRaw('SUM(CASE WHEN status = ? AND updated_at >= ? THEN 1 ELSE 0 END) AS delivered_this_month', [
                OrderStatus::Delivered->value,
                now()->startOfMonth(),
            ])
            ->first();

        $pendingDrivers = DriverProfile::query()
            ->where('verification_status', DriverVerificationStatus::Pending->value)
            ->count();

        $pendingReviews = Review::query()
            ->where('status', ReviewStatus::Pending->value)
            ->count();

        return [
            Stat::make('سفارش‌های امروز', $this->format($orders?->today))
                ->icon(Heroicon::OutlinedClipboardDocumentList),
            Stat::make('در انتظار پیشنهاد', $this->format($orders?->open_for_offers))
                ->icon(Heroicon::OutlinedHandThumbUp)
                ->color('info'),
            Stat::make('در مسیر', $this->format($orders?->in_transit))
                ->icon(Heroicon::OutlinedTruck)
                ->color('primary'),
            Stat::make('تحویل شده در این ماه', $this->format($orders?->delivered_this_month))
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success'),
            Stat::make('رانندگان در انتظار تأیید', $this->format($pendingDrivers))
                ->icon(Heroicon::OutlinedIdentification)
                ->color($pendingDrivers > 0 ? 'warning' : 'gray'),
            Stat::make('نظرات در انتظار بررسی', $this->format($pendingReviews))
                ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                ->color($pendingReviews > 0 ? 'warning' : 'gray'),
        ];
    }

    protected function format(mixed $count): string
    {
        return Jalali::number((int) $count);
    }
}
