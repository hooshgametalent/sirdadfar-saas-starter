<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Exceptions\FreightException;
use App\Models\Offer;
use App\Services\OrderLifecycle;
use App\Support\Jalali;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;

class OffersRelationManager extends RelationManager
{
    protected static string $relationship = 'offers';

    protected static ?string $title = 'پیشنهادهای رانندگان';

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('پیشنهاد')
            ->pluralModelLabel('پیشنهادها')
            ->defaultSort('price')
            ->columns([
                TextColumn::make('driver.name')->label('راننده'),
                TextColumn::make('driver.mobile')->label('موبایل راننده'),
                TextColumn::make('price')
                    ->label('مبلغ پیشنهادی')
                    ->formatStateUsing(fn ($state): string => Jalali::formatToman($state))
                    ->sortable(),
                TextColumn::make('message')->label('پیام')->placeholder('-')->limit(60),
                TextColumn::make('status')->label('وضعیت')->badge(),
                TextColumn::make('created_at')
                    ->label('زمان ثبت')
                    ->formatStateUsing(fn ($state): string => Jalali::dateTime($state)),
            ])
            ->recordActions([
                Action::make('accept')
                    ->label('پذیرش این پیشنهاد')
                    ->icon(Heroicon::OutlinedHandThumbUp)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Offer $record): bool => $record->status === OfferStatus::Pending
                        && $this->getOwnerRecord()->status === OrderStatus::OpenForOffers)
                    ->action(function (Offer $record): void {
                        try {
                            app(OrderLifecycle::class)->acceptOffer($record, auth()->user());
                        } catch (FreightException|AuthorizationException $exception) {
                            Notification::make()
                                ->title('انجام این کار ممکن نیست')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        $this->getOwnerRecord()->refresh();

                        Notification::make()->title('سفارش به راننده تخصیص یافت.')->success()->send();
                    }),
            ]);
    }
}
