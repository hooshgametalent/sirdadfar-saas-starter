<?php

namespace App\Filament\Resources;

use App\Enums\PaymentDirection;
use App\Enums\PaymentStatus;
use App\Filament\Resources\PaymentResource\Pages\CreatePayment;
use App\Filament\Resources\PaymentResource\Pages\EditPayment;
use App\Filament\Resources\PaymentResource\Pages\ListPayments;
use App\Models\Payment;
use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use App\Support\Jalali;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use UnitEnum;

/**
 * Manual settlements recorded by admins. No payment gateway is involved.
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_FINANCE;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'تسویه';

    protected static ?string $pluralModelLabel = 'پرداخت‌ها و تسویه‌ها';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('user_id')
                    ->label('طرف حساب')
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->name.' ('.$record->role->label().' - '.$record->mobile.')')
                    ->searchable(['name', 'mobile'])
                    ->required(),
                Select::make('order_id')
                    ->label('سفارش مرتبط (اختیاری)')
                    ->relationship('order', 'reference')
                    ->searchable(),
                Select::make('direction')
                    ->label('نوع تراکنش')
                    ->options(PaymentDirection::class)
                    ->required(),
                TextInput::make('amount')
                    ->label('مبلغ')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->suffix('تومان')
                    ->required(),
                Select::make('status')
                    ->label('وضعیت')
                    ->options(PaymentStatus::class)
                    ->default(PaymentStatus::Pending->value)
                    ->required(),
                DateTimePicker::make('paid_at')
                    ->label('زمان پرداخت')
                    ->live()
                    ->hint(fn ($state): ?string => filled($state) ? 'معادل شمسی: '.Jalali::dateTime(Carbon::parse($state)) : null),
                TextInput::make('reference_note')
                    ->label('شماره پیگیری یا توضیح')
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('طرف حساب')->searchable(),
                TextColumn::make('order.reference')->label('سفارش')->placeholder('-')->searchable(),
                TextColumn::make('direction')->label('نوع')->badge(),
                TextColumn::make('amount')
                    ->label('مبلغ')
                    ->formatStateUsing(fn ($state): string => Jalali::formatToman($state))
                    ->sortable(),
                TextColumn::make('status')->label('وضعیت')->badge(),
                TextColumn::make('paid_at')
                    ->label('زمان پرداخت')
                    ->placeholder('-')
                    ->formatStateUsing(fn ($state): string => Jalali::dateTime($state))
                    ->sortable(),
                TextColumn::make('recorder.name')->label('ثبت کننده')->placeholder('-')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('direction')->label('نوع')->options(PaymentDirection::class),
                SelectFilter::make('status')->label('وضعیت')->options(PaymentStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function auditProperties(Payment $payment): array
    {
        return [
            'user_id' => $payment->user_id,
            'order_id' => $payment->order_id,
            'direction' => $payment->direction?->value,
            'amount' => $payment->amount,
            'status' => $payment->status?->value,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }
}
