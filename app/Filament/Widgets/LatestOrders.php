<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Support\Jalali;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isStaff() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('آخرین سفارش‌ها')
            ->query(Order::query()->with(['shipper:id,name', 'originCity:id,name', 'destinationCity:id,name'])->latest()->limit(8))
            ->paginated(false)
            ->recordUrl(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('reference')->label('کد پیگیری'),
                TextColumn::make('status')->label('وضعیت')->badge(),
                TextColumn::make('shipper.name')->label('صاحب بار'),
                TextColumn::make('originCity.name')->label('مبدأ'),
                TextColumn::make('destinationCity.name')->label('مقصد'),
                TextColumn::make('offered_price')
                    ->label('قیمت پیشنهادی')
                    ->formatStateUsing(fn ($state): string => Jalali::formatToman($state)),
                TextColumn::make('created_at')
                    ->label('زمان ثبت')
                    ->formatStateUsing(fn ($state): string => Jalali::dateTime($state)),
            ]);
    }
}
