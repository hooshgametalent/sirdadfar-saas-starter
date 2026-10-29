<?php

namespace App\Filament\Resources;

use App\Enums\PublishStatus;
use App\Filament\Resources\ArticleResource\Pages\CreateArticle;
use App\Filament\Resources\ArticleResource\Pages\EditArticle;
use App\Filament\Resources\ArticleResource\Pages\ListArticles;
use App\Models\Article;
use App\Providers\Filament\AdminPanelProvider;
use App\Support\Jalali;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ArticleResource extends Resource
{
    public const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * Toolbar limited to tags kept by App\Support\Html::clean.
     *
     * @var array<int, array<int, string>>
     */
    public const EDITOR_TOOLBAR = [
        ['bold', 'italic', 'underline', 'link'],
        ['h2', 'h3'],
        ['blockquote', 'bulletList', 'orderedList'],
        ['undo', 'redo'],
    ];

    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_CONTENT;

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'مقاله';

    protected static ?string $pluralModelLabel = 'مقالات';

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
                            ->helperText('فقط حروف کوچک لاتین، عدد و خط تیره. مثال: tehran-isfahan-guide')
                            ->required()
                            ->maxLength(255)
                            ->regex(self::SLUG_PATTERN)
                            ->unique(ignoreRecord: true)
                            ->extraInputAttributes(['dir' => 'ltr']),
                        Textarea::make('excerpt')->label('خلاصه')->rows(3)->maxLength(1000),
                        RichEditor::make('body')
                            ->label('متن مقاله')
                            ->required()
                            ->fileAttachments(false)
                            ->toolbarButtons(self::EDITOR_TOOLBAR),
                    ]),
                Section::make('انتشار')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Select::make('category_id')
                            ->label('دسته‌بندی')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->label('وضعیت')
                            ->options(PublishStatus::class)
                            ->default(PublishStatus::Draft->value)
                            ->required(),
                        DateTimePicker::make('published_at')
                            ->label('زمان انتشار')
                            ->helperText('برای انتشار فوری خالی بگذارید یا زمان فعلی را انتخاب کنید.'),
                    ]),
                self::seoSection(),
            ]);
    }

    public static function seoSection(): Section
    {
        return Section::make('سئو')
            ->columnSpanFull()
            ->collapsible()
            ->schema([
                TextInput::make('seo_title')->label('عنوان سئو')->maxLength(255),
                Textarea::make('seo_description')->label('توضیحات متا')->rows(2)->maxLength(500),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('عنوان')->searchable()->limit(60),
                TextColumn::make('category.name')->label('دسته‌بندی')->placeholder('-'),
                TextColumn::make('status')->label('وضعیت')->badge(),
                TextColumn::make('published_at')
                    ->label('زمان انتشار')
                    ->placeholder('-')
                    ->formatStateUsing(fn ($state): string => Jalali::dateTime($state))
                    ->sortable(),
                TextColumn::make('author.name')->label('نویسنده')->placeholder('-')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(PublishStatus::class),
                SelectFilter::make('category_id')->label('دسته‌بندی')->relationship('category', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    /**
     * Publishing without a date publishes immediately.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function preparePublishDate(array $data): array
    {
        $status = $data['status'] ?? null;
        $status = $status instanceof PublishStatus ? $status : PublishStatus::tryFrom((string) $status);

        if ($status === PublishStatus::Published && blank($data['published_at'] ?? null)) {
            $data['published_at'] = now();
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
