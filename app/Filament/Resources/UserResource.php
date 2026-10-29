<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use App\Support\Jalali;
use App\Support\Mobile;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_ADMIN;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'کاربر';

    protected static ?string $pluralModelLabel = 'کاربران';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('نام و نام خانوادگی')
                    ->required()
                    ->maxLength(255),
                TextInput::make('mobile')
                    ->label('شماره موبایل')
                    ->required()
                    ->tel()
                    ->mutateStateForValidationUsing(fn (?string $state): string => Mobile::normalize($state))
                    ->dehydrateStateUsing(fn (?string $state): string => Mobile::normalize($state))
                    ->regex(Mobile::PATTERN)
                    ->validationMessages(['regex' => 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.'])
                    ->unique(ignoreRecord: true)
                    ->extraInputAttributes(['dir' => 'ltr']),
                TextInput::make('email')
                    ->label('ایمیل (اختیاری)')
                    ->email()
                    ->nullable()
                    ->unique(ignoreRecord: true)
                    ->extraInputAttributes(['dir' => 'ltr']),
                Select::make('role')
                    ->label('نقش')
                    ->options(UserRole::class)
                    ->required()
                    ->disabled(fn (?User $record): bool => self::isCurrentUser($record))
                    ->helperText(fn (?User $record): ?string => self::isCurrentUser($record) ? 'نقش حساب خودتان قابل تغییر نیست.' : null),
                Toggle::make('is_active')
                    ->label('حساب فعال است')
                    ->default(true)
                    ->disabled(fn (?User $record): bool => self::isCurrentUser($record)),
                TextInput::make('password')
                    ->label(fn (string $operation): string => $operation === 'create' ? 'رمز عبور' : 'رمز عبور جدید (فقط برای بازنشانی پر کنید)')
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->maxLength(255)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->autocomplete('new-password'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('نام')->searchable()->sortable(),
                TextColumn::make('mobile')->label('موبایل')->searchable(),
                TextColumn::make('role')->label('نقش')->badge(),
                IconColumn::make('is_active')->label('فعال')->boolean(),
                TextColumn::make('created_at')
                    ->label('تاریخ عضویت')
                    ->formatStateUsing(fn ($state): string => Jalali::date($state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')->label('نقش')->options(UserRole::class),
                TernaryFilter::make('is_active')->label('وضعیت حساب'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function isCurrentUser(?User $record): bool
    {
        return $record !== null && $record->is(auth()->user());
    }
}
