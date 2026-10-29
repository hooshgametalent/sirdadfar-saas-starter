<?php

namespace App\Filament\Resources\OrderResource;

use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Exceptions\FreightException;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use App\Services\OrderLifecycle;
use App\Support\Jalali;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Order lifecycle actions shared by the order table and the order view page.
 * Every transition goes through OrderLifecycle, which validates the status
 * change, locks the row and writes the status history and audit log.
 */
class OrderActions
{
    /**
     * @return array<int, Action>
     */
    public static function all(): array
    {
        return [
            self::approve(),
            self::assignOffer(),
            self::markLoaded(),
            self::markInTransit(),
            self::markDelivered(),
            self::cancel(),
        ];
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('تأیید سفارش')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->authorize('approve')
            ->requiresConfirmation()
            ->modalDescription('پس از تأیید، سفارش برای دریافت پیشنهاد رانندگان باز می‌شود.')
            ->schema([
                Textarea::make('note')->label('یادداشت (اختیاری)')->maxLength(1000),
            ])
            ->action(fn (Order $record, array $data) => self::run(
                fn (OrderLifecycle $lifecycle, User $user) => $lifecycle->approve($record, $user, $data['note'] ?? null),
                'سفارش تأیید شد و برای دریافت پیشنهاد باز است.',
            ));
    }

    public static function assignOffer(): Action
    {
        return Action::make('assignOffer')
            ->label('تخصیص به راننده')
            ->icon(Heroicon::OutlinedTruck)
            ->color('primary')
            ->authorize('acceptOffer')
            ->schema([
                Select::make('offer_id')
                    ->label('پیشنهاد راننده')
                    ->required()
                    ->options(fn (Order $record): array => $record->offers()
                        ->pending()
                        ->fromEligibleDrivers()
                        ->with('driver:id,name')
                        ->orderBy('price')
                        ->get()
                        ->mapWithKeys(fn (Offer $offer): array => [
                            $offer->getKey() => ($offer->driver?->name ?? 'راننده').' - '.Jalali::formatToman($offer->price),
                        ])
                        ->all()),
            ])
            ->action(function (Order $record, array $data): void {
                $offer = $record->offers()
                    ->whereKey($data['offer_id'])
                    ->where('status', OfferStatus::Pending->value)
                    ->first();

                if ($offer === null) {
                    Notification::make()->title('این پیشنهاد دیگر قابل پذیرش نیست.')->danger()->send();

                    return;
                }

                self::run(
                    fn (OrderLifecycle $lifecycle, User $user) => $lifecycle->acceptOffer($offer, $user),
                    'سفارش به راننده تخصیص یافت.',
                );

                $record->refresh();
            });
    }

    public static function markLoaded(): Action
    {
        return Action::make('markLoaded')
            ->label('ثبت بارگیری')
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('info')
            ->authorize('updateProgress')
            ->visible(fn (Order $record): bool => $record->status === OrderStatus::Assigned)
            ->requiresConfirmation()
            ->action(fn (Order $record) => self::run(
                fn (OrderLifecycle $lifecycle, User $user) => $lifecycle->markLoaded($record, $user),
                'بارگیری سفارش ثبت شد.',
            ));
    }

    public static function markInTransit(): Action
    {
        return Action::make('markInTransit')
            ->label('ثبت حرکت')
            ->icon(Heroicon::OutlinedMapPin)
            ->color('info')
            ->authorize('updateProgress')
            ->visible(fn (Order $record): bool => $record->status === OrderStatus::Loaded)
            ->requiresConfirmation()
            ->action(fn (Order $record) => self::run(
                fn (OrderLifecycle $lifecycle, User $user) => $lifecycle->markInTransit($record, $user),
                'سفارش در مسیر قرار گرفت.',
            ));
    }

    public static function markDelivered(): Action
    {
        return Action::make('markDelivered')
            ->label('ثبت تحویل')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->authorize('updateProgress')
            ->visible(fn (Order $record): bool => $record->status === OrderStatus::InTransit)
            ->requiresConfirmation()
            ->action(fn (Order $record) => self::run(
                fn (OrderLifecycle $lifecycle, User $user) => $lifecycle->markDelivered($record, $user),
                'تحویل سفارش ثبت شد.',
            ));
    }

    public static function cancel(): Action
    {
        return Action::make('cancel')
            ->label('لغو سفارش')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->authorize('cancel')
            ->requiresConfirmation()
            ->schema([
                Textarea::make('reason')->label('دلیل لغو')->required()->maxLength(1000),
            ])
            ->action(fn (Order $record, array $data) => self::run(
                fn (OrderLifecycle $lifecycle, User $user) => $lifecycle->cancel($record, $user, $data['reason']),
                'سفارش لغو شد.',
            ));
    }

    /**
     * Run a lifecycle call and turn business rule errors into Persian
     * notifications instead of error pages.
     *
     * @param  Closure(OrderLifecycle, User): mixed  $callback
     */
    protected static function run(Closure $callback, string $success): bool
    {
        try {
            $callback(app(OrderLifecycle::class), auth()->user());
        } catch (FreightException|AuthorizationException $exception) {
            Notification::make()
                ->title('انجام این کار ممکن نیست')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return false;
        }

        Notification::make()->title($success)->success()->send();

        return true;
    }
}
