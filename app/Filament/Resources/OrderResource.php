<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\OrderActions;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Filament\Resources\OrderResource\RelationManagers\OffersRelationManager;
use App\Filament\Resources\OrderResource\RelationManagers\StatusHistoriesRelationManager;
use App\Models\Order;
use App\Models\Province;
use App\Providers\Filament\AdminPanelProvider;
use App\Support\Jalali;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_ORDERS;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'سفارش';

    protected static ?string $pluralModelLabel = 'سفارش‌ها';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function canCreate(): bool
    {
        // Orders are submitted by shippers from their own panel.
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('مشخصات سفارش')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('reference')->label('کد پیگیری'),
                        TextEntry::make('status')->label('وضعیت')->badge(),
                        TextEntry::make('created_at')
                            ->label('تاریخ ثبت')
                            ->formatStateUsing(fn ($state): string => Jalali::dateTime($state)),
                        TextEntry::make('shipper.name')->label('صاحب بار'),
                        TextEntry::make('shipper.mobile')->label('موبایل صاحب بار'),
                        TextEntry::make('assignedDriver.name')->label('راننده')->placeholder('تخصیص نیافته'),
                    ]),
                Section::make('مسیر و بار')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('originCity.name')->label('مبدأ'),
                        TextEntry::make('destinationCity.name')->label('مقصد'),
                        TextEntry::make('vehicleType.name')->label('نوع خودرو'),
                        TextEntry::make('origin_address')->label('نشانی مبدأ')->columnSpan(['md' => 3]),
                        TextEntry::make('destination_address')->label('نشانی مقصد')->columnSpan(['md' => 3]),
                        TextEntry::make('cargo_type')->label('نوع بار'),
                        TextEntry::make('weight_kg')
                            ->label('وزن')
                            ->formatStateUsing(fn ($state): string => Jalali::number((int) $state).' کیلوگرم'),
                        TextEntry::make('pickup_date')
                            ->label('تاریخ بارگیری')
                            ->formatStateUsing(fn ($state): string => Jalali::date($state)),
                        TextEntry::make('requirements')
                            ->label('نیازهای ویژه')
                            ->state(fn (Order $record): string => self::requirementsLabel($record))
                            ->columnSpan(['md' => 3]),
                        TextEntry::make('notes')->label('توضیحات')->placeholder('-')->columnSpan(['md' => 3]),
                    ]),
                Section::make('مالی')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('offered_price')
                            ->label('قیمت پیشنهادی صاحب بار')
                            ->formatStateUsing(fn ($state): string => Jalali::formatToman($state)),
                        TextEntry::make('final_price')
                            ->label('قیمت نهایی')
                            ->placeholder('-')
                            ->formatStateUsing(fn ($state): string => Jalali::formatToman($state)),
                        TextEntry::make('cancelled_reason')->label('دلیل لغو')->placeholder('-'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')->label('کد پیگیری')->searchable()->sortable(),
                TextColumn::make('status')->label('وضعیت')->badge(),
                TextColumn::make('shipper.name')->label('صاحب بار')->searchable(),
                TextColumn::make('originCity.name')->label('مبدأ'),
                TextColumn::make('destinationCity.name')->label('مقصد'),
                TextColumn::make('vehicleType.name')->label('نوع خودرو')->toggleable(),
                TextColumn::make('offered_price')
                    ->label('قیمت پیشنهادی')
                    ->formatStateUsing(fn ($state): string => Jalali::formatToman($state)),
                TextColumn::make('pickup_date')
                    ->label('تاریخ بارگیری')
                    ->formatStateUsing(fn ($state): string => Jalali::date($state))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->formatStateUsing(fn ($state): string => Jalali::dateTime($state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(OrderStatus::class),
                SelectFilter::make('vehicle_type_id')
                    ->label('نوع خودرو')
                    ->relationship('vehicleType', 'name'),
                SelectFilter::make('origin_province')
                    ->label('استان مبدأ')
                    ->options(fn (): array => Province::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, $provinceId): Builder => $query->whereHas(
                            'originCity',
                            fn (Builder $city): Builder => $city->where('province_id', $provinceId),
                        ),
                    )),
                Filter::make('created_at')
                    ->label('تاریخ ثبت')
                    ->schema([
                        DatePicker::make('from')->label('از تاریخ'),
                        DatePicker::make('until')->label('تا تاریخ'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
                ...OrderActions::all(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OffersRelationManager::class,
            StatusHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }

    public static function requirementsLabel(Order $order): string
    {
        $labels = collect($order->special_requirements ?? [])
            ->filter()
            ->keys()
            ->map(fn (string $key): string => Order::SPECIAL_REQUIREMENTS[$key] ?? $key);

        return $labels->isEmpty() ? '-' : $labels->implode('، ');
    }
}
