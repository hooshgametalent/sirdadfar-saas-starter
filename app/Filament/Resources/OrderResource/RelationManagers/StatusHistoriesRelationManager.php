<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use App\Support\Jalali;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StatusHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistories';

    protected static ?string $title = 'تاریخچه وضعیت';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('تغییر وضعیت')
            ->pluralModelLabel('تاریخچه وضعیت')
            ->columns([
                TextColumn::make('from_status')->label('از وضعیت')->badge()->placeholder('-'),
                TextColumn::make('to_status')->label('به وضعیت')->badge(),
                TextColumn::make('changedBy.name')->label('توسط')->placeholder('سیستم'),
                TextColumn::make('note')->label('یادداشت')->placeholder('-')->wrap(),
                TextColumn::make('created_at')
                    ->label('زمان')
                    ->formatStateUsing(fn ($state): string => Jalali::dateTime($state)),
            ]);
    }
}
