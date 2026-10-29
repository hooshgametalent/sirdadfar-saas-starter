<?php

namespace App\Filament\Resources;

use App\Enums\ReviewStatus;
use App\Filament\Resources\ReviewResource\Pages\ManageReviews;
use App\Models\Review;
use App\Providers\Filament\AdminPanelProvider;
use App\Services\ReviewService;
use App\Support\Jalali;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_CONTENT;

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'نظر مشتری';

    protected static ?string $pluralModelLabel = 'نظرات مشتریان';

    public static function canCreate(): bool
    {
        // Reviews are written by shippers after delivery.
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Review::query()->where('status', ReviewStatus::Pending->value)->count();

        return $pending > 0 ? Jalali::toPersianDigits($pending) : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order.reference')->label('سفارش')->searchable(),
                TextColumn::make('display_name')->label('نام نمایشی'),
                TextColumn::make('rating')
                    ->label('امتیاز')
                    ->formatStateUsing(fn ($state): string => Jalali::toPersianDigits((int) $state).' از ۵')
                    ->sortable(),
                TextColumn::make('body')->label('متن نظر')->limit(80)->wrap(),
                IconColumn::make('publish_consent')->label('رضایت انتشار')->boolean(),
                TextColumn::make('status')->label('وضعیت')->badge(),
                IconColumn::make('is_featured')->label('ویژه')->boolean(),
                TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->formatStateUsing(fn ($state): string => Jalali::date($state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(ReviewStatus::class),
                TernaryFilter::make('publish_consent')->label('رضایت انتشار'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('تأیید و پاسخ')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->authorize('update')
                    ->fillForm(fn (Review $record): array => [
                        'admin_reply' => $record->admin_reply,
                        'is_featured' => $record->is_featured,
                    ])
                    ->schema([
                        Textarea::make('admin_reply')->label('پاسخ رسمی (اختیاری)')->maxLength(2000),
                        Toggle::make('is_featured')
                            ->label('نمایش به عنوان نظر ویژه')
                            ->visible(fn (Review $record): bool => $record->publish_consent),
                    ])
                    ->action(function (Review $record, array $data): void {
                        app(ReviewService::class)->moderate(
                            $record,
                            auth()->user(),
                            ReviewStatus::Approved,
                            filled($data['admin_reply'] ?? null) ? $data['admin_reply'] : null,
                            $record->publish_consent && (bool) ($data['is_featured'] ?? false),
                        );

                        Notification::make()->title('نظر تأیید شد.')->success()->send();
                    }),
                Action::make('toggleFeatured')
                    ->label(fn (Review $record): string => $record->is_featured ? 'حذف از ویژه‌ها' : 'افزودن به ویژه‌ها')
                    ->icon(Heroicon::OutlinedStar)
                    ->color('warning')
                    ->authorize('update')
                    ->visible(fn (Review $record): bool => $record->publish_consent && $record->status === ReviewStatus::Approved)
                    ->action(function (Review $record): void {
                        app(ReviewService::class)->moderate(
                            $record,
                            auth()->user(),
                            ReviewStatus::Approved,
                            $record->admin_reply,
                            ! $record->is_featured,
                        );

                        Notification::make()->title('وضعیت ویژه بودن نظر به‌روز شد.')->success()->send();
                    }),
                Action::make('reject')
                    ->label('رد نظر')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->authorize('update')
                    ->visible(fn (Review $record): bool => $record->status !== ReviewStatus::Rejected)
                    ->requiresConfirmation()
                    ->schema([
                        Textarea::make('admin_reply')->label('یادداشت داخلی یا پاسخ (اختیاری)')->maxLength(2000),
                    ])
                    ->action(function (Review $record, array $data): void {
                        app(ReviewService::class)->moderate(
                            $record,
                            auth()->user(),
                            ReviewStatus::Rejected,
                            filled($data['admin_reply'] ?? null) ? $data['admin_reply'] : null,
                        );

                        Notification::make()->title('نظر رد شد.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageReviews::route('/'),
        ];
    }
}
