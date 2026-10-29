<?php

namespace App\Filament\Resources;

use App\Enums\PageType;
use App\Enums\PublishStatus;
use App\Filament\Resources\PageResource\Pages\CreatePage;
use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Filament\Resources\PageResource\Pages\ListPages;
use App\Models\Page;
use App\Providers\Filament\AdminPanelProvider;
use App\Support\Jalali;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_CONTENT;

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'صفحه';

    protected static ?string $pluralModelLabel = 'صفحات (مسیر، خدمت، ثابت)';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('محتوا')
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        TextInput::make('title')->label('عنوان')->required()->maxLength(255),
                        TextInput::make('slug')
                            ->label('نامک (لاتین)')
                            ->helperText('مثال: tehran-to-isfahan')
                            ->required()
                            ->maxLength(255)
                            ->regex(ArticleResource::SLUG_PATTERN)
                            ->unique(ignoreRecord: true)
                            ->extraInputAttributes(['dir' => 'ltr']),
                        RichEditor::make('body')
                            ->label('متن صفحه')
                            ->required()
                            ->fileAttachments(false)
                            ->toolbarButtons(ArticleResource::EDITOR_TOOLBAR),
                    ]),
                Section::make('نوع و انتشار')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Select::make('type')
                            ->label('نوع صفحه')
                            ->options(PageType::class)
                            ->default(PageType::StaticPage->value)
                            ->required()
                            ->live(),
                        Select::make('origin_city_id')
                            ->label('شهر مبدأ')
                            ->relationship('originCity', 'name')
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get): bool => self::isRouteType($get('type')))
                            ->required(fn (Get $get): bool => self::isRouteType($get('type'))),
                        Select::make('destination_city_id')
                            ->label('شهر مقصد')
                            ->relationship('destinationCity', 'name')
                            ->searchable()
                            ->preload()
                            ->different('origin_city_id')
                            ->visible(fn (Get $get): bool => self::isRouteType($get('type')))
                            ->required(fn (Get $get): bool => self::isRouteType($get('type'))),
                        Select::make('status')
                            ->label('وضعیت')
                            ->options(PublishStatus::class)
                            ->default(PublishStatus::Draft->value)
                            ->required(),
                    ]),
                ArticleResource::seoSection(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('عنوان')->searchable()->limit(60),
                TextColumn::make('slug')->label('نامک')->searchable(),
                TextColumn::make('type')->label('نوع')->badge(),
                TextColumn::make('status')->label('وضعیت')->badge(),
                TextColumn::make('updated_at')
                    ->label('آخرین ویرایش')
                    ->formatStateUsing(fn ($state): string => Jalali::dateTime($state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->label('نوع')->options(PageType::class),
                SelectFilter::make('status')->label('وضعیت')->options(PublishStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function isRouteType(mixed $type): bool
    {
        return $type === PageType::Route || $type === PageType::Route->value;
    }

    /**
     * Non route pages never keep origin or destination cities.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function clearCitiesUnlessRoute(array $data): array
    {
        if (! self::isRouteType($data['type'] ?? null)) {
            $data['origin_city_id'] = null;
            $data['destination_city_id'] = null;
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
