<?php

namespace App\Filament\Resources\DriverProfileResource\RelationManagers;

use App\Enums\ReviewStatus;
use App\Models\DriverDocument;
use App\Services\DriverVerificationService;
use App\Support\Jalali;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'مدارک بارگذاری شده';

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('مدرک')
            ->pluralModelLabel('مدارک')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('type')->label('نوع مدرک')->badge(),
                TextColumn::make('original_name')->label('نام فایل')->limit(40),
                TextColumn::make('size')
                    ->label('حجم')
                    ->formatStateUsing(fn ($state): string => Jalali::number((int) ceil(((int) $state) / 1024)).' کیلوبایت'),
                TextColumn::make('status')->label('وضعیت')->badge(),
                TextColumn::make('created_at')
                    ->label('زمان بارگذاری')
                    ->formatStateUsing(fn ($state): string => Jalali::dateTime($state)),
            ])
            ->recordActions([
                // Files live on the private disk and are streamed by the
                // authorized documents.show route, never from a public URL.
                Action::make('download')
                    ->label('مشاهده فایل')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->authorize('view')
                    ->url(fn (DriverDocument $record): string => route('documents.show', $record), shouldOpenInNewTab: true),
                Action::make('approveDocument')
                    ->label('تأیید مدرک')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->authorize('update')
                    ->visible(fn (DriverDocument $record): bool => $record->status !== ReviewStatus::Approved)
                    ->action(fn (DriverDocument $record) => $this->reviewDocument($record, ReviewStatus::Approved)),
                Action::make('rejectDocument')
                    ->label('رد مدرک')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->authorize('update')
                    ->visible(fn (DriverDocument $record): bool => $record->status !== ReviewStatus::Rejected)
                    ->requiresConfirmation()
                    ->action(fn (DriverDocument $record) => $this->reviewDocument($record, ReviewStatus::Rejected)),
            ]);
    }

    protected function reviewDocument(DriverDocument $document, ReviewStatus $status): void
    {
        app(DriverVerificationService::class)->reviewDocument($document, auth()->user(), $status);

        Notification::make()
            ->title($status === ReviewStatus::Approved ? 'مدرک تأیید شد.' : 'مدرک رد شد.')
            ->success()
            ->send();
    }
}
