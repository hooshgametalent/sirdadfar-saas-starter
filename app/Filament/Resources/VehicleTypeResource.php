<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehicleTypeResource\Pages\ManageVehicleTypes;
use App\Models\VehicleType;
use App\Providers\Filament\AdminPanelProvider;
use App\Support\Jalali;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class VehicleTypeResource extends Resource
{
    protected static ?string $model = VehicleType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_FLEET;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'نوع خودرو';

    protected static ?string $pluralModelLabel = 'انواع خودرو';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->label('نام')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true),
                TextInput::make('slug')
                    ->label('نامک (لاتین)')
                    ->required()
                    ->maxLength(100)
                    ->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->unique(ignoreRecord: true)
                    ->extraInputAttributes(['dir' => 'ltr']),
                TextInput::make('capacity_kg')
                    ->label('ظرفیت')
                    ->numeric()
                    ->minValue(0)
                    ->suffix('کیلوگرم'),
                TextInput::make('sort_order')
                    ->label('ترتیب نمایش')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('نام')->searchable(),
                TextColumn::make('slug')->label('نامک'),
                TextColumn::make('capacity_kg')
                    ->label('ظرفیت')
                    ->placeholder('-')
                    ->formatStateUsing(fn ($state): string => Jalali::number((int) $state).' کیلوگرم'),
                TextColumn::make('sort_order')->label('ترتیب')->sortable(),
                IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageVehicleTypes::route('/'),
        ];
    }
}
