<?php

namespace App\Filament\Resources;

use App\Enums\DriverVerificationStatus;
use App\Exceptions\FreightException;
use App\Filament\Resources\DriverProfileResource\Pages\ListDriverProfiles;
use App\Filament\Resources\DriverProfileResource\Pages\ViewDriverProfile;
use App\Filament\Resources\DriverProfileResource\RelationManagers\DocumentsRelationManager;
use App\Models\DriverProfile;
use App\Providers\Filament\AdminPanelProvider;
use App\Services\DriverVerificationService;
use App\Support\Jalali;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Auth\Access\AuthorizationException;
use UnitEnum;

class DriverProfileResource extends Resource
{
    protected static ?string $model = DriverProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_FLEET;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'راننده';

    protected static ?string $pluralModelLabel = 'رانندگان و مدارک';

    public static function canCreate(): bool
    {
        // Drivers create their own profile from the driver panel.
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = DriverProfile::query()
            ->where('verification_status', DriverVerificationStatus::Pending->value)
            ->count();

        return $pending > 0 ? Jalali::toPersianDigits($pending) : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('مشخصات راننده')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('user.name')->label('نام'),
                        TextEntry::make('user.mobile')->label('موبایل'),
                        TextEntry::make('national_code')->label('کد ملی')->placeholder('-'),
                        TextEntry::make('vehicleType.name')->label('نوع خودرو'),
                        TextEntry::make('plate_number')->label('پلاک'),
                        TextEntry::make('vehicle_model')->label('مدل خودرو')->placeholder('-'),
                        TextEntry::make('capacity_kg')
                            ->label('ظرفیت')
                            ->placeholder('-')
                            ->formatStateUsing(fn ($state): string => Jalali::number((int) $state).' کیلوگرم'),
                        TextEntry::make('homeCity.name')->label('شهر محل فعالیت')->placeholder('-'),
                    ]),
                Section::make('وضعیت تأیید')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('verification_status')->label('وضعیت')->badge(),
                        TextEntry::make('verifier.name')->label('بررسی شده توسط')->placeholder('-'),
                        TextEntry::make('verified_at')
                            ->label('زمان تأیید')
                            ->placeholder('-')
                            ->formatStateUsing(fn ($state): string => Jalali::dateTime($state)),
                        TextEntry::make('rejection_reason')->label('دلیل رد')->placeholder('-')->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')->label('نام')->searchable(),
                TextColumn::make('user.mobile')->label('موبایل')->searchable(),
                TextColumn::make('vehicleType.name')->label('نوع خودرو'),
                TextColumn::make('plate_number')->label('پلاک'),
                TextColumn::make('verification_status')->label('وضعیت')->badge(),
                TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->formatStateUsing(fn ($state): string => Jalali::date($state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('verification_status')
                    ->label('وضعیت')
                    ->options(DriverVerificationStatus::class),
                SelectFilter::make('vehicle_type_id')
                    ->label('نوع خودرو')
                    ->relationship('vehicleType', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    /**
     * @return array<int, Action>
     */
    public static function verificationActions(): array
    {
        return [
            Action::make('approve')
                ->label('تأیید راننده')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->authorize('verify')
                ->visible(fn (DriverProfile $record): bool => $record->verification_status !== DriverVerificationStatus::Approved)
                ->requiresConfirmation()
                ->action(fn (DriverProfile $record) => self::runVerification(
                    fn (DriverVerificationService $service) => $service->approve($record, auth()->user()),
                    'راننده تأیید شد.',
                )),
            Action::make('reject')
                ->label('رد مدارک')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->authorize('verify')
                ->visible(fn (DriverProfile $record): bool => $record->verification_status !== DriverVerificationStatus::Rejected)
                ->schema([
                    Textarea::make('reason')->label('دلیل رد')->required()->maxLength(1000),
                ])
                ->action(fn (DriverProfile $record, array $data) => self::runVerification(
                    fn (DriverVerificationService $service) => $service->reject($record, auth()->user(), $data['reason']),
                    'مدارک راننده رد شد.',
                )),
        ];
    }

    public static function getRelations(): array
    {
        return [
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDriverProfiles::route('/'),
            'view' => ViewDriverProfile::route('/{record}'),
        ];
    }

    protected static function runVerification(Closure $callback, string $success): void
    {
        try {
            $callback(app(DriverVerificationService::class));
        } catch (FreightException|AuthorizationException $exception) {
            Notification::make()
                ->title('انجام این کار ممکن نیست')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()->title($success)->success()->send();
    }
}
