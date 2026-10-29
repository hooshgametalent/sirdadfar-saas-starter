<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages\ListAuditLogs;
use App\Models\AuditLog;
use App\Providers\Filament\AdminPanelProvider;
use App\Support\Jalali;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Read only list of sensitive actions. Records are written only by
 * App\Services\AuditLogger.
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_ADMIN;

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'رویداد';

    protected static ?string $pluralModelLabel = 'گزارش رویدادها';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('زمان')
                    ->formatStateUsing(fn ($state): string => Jalali::dateTime($state))
                    ->sortable(),
                TextColumn::make('user.name')->label('کاربر')->placeholder('سیستم')->searchable(),
                TextColumn::make('action')->label('رویداد')->badge()->color('gray')->searchable(),
                TextColumn::make('auditable_type')
                    ->label('موضوع')
                    ->placeholder('-')
                    ->formatStateUsing(fn (?string $state, AuditLog $record): string => class_basename((string) $state).' #'.$record->auditable_id),
                TextColumn::make('details')
                    ->label('جزئیات')
                    ->state(fn (AuditLog $record): ?string => $record->properties
                        ? json_encode($record->properties, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        : null)
                    ->placeholder('-')
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('نوع رویداد')
                    ->options(fn (): array => AuditLog::query()
                        ->distinct()
                        ->orderBy('action')
                        ->pluck('action', 'action')
                        ->all()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
