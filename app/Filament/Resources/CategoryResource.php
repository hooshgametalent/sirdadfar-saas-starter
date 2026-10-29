<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages\ManageCategories;
use App\Models\Category;
use App\Providers\Filament\AdminPanelProvider;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_CONTENT;

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'دسته‌بندی';

    protected static ?string $pluralModelLabel = 'دسته‌بندی‌ها';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('نام')->required()->maxLength(150),
                TextInput::make('slug')
                    ->label('نامک (لاتین)')
                    ->required()
                    ->maxLength(150)
                    ->regex(ArticleResource::SLUG_PATTERN)
                    ->unique(ignoreRecord: true)
                    ->extraInputAttributes(['dir' => 'ltr']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('نام')->searchable(),
                TextColumn::make('slug')->label('نامک'),
                TextColumn::make('articles_count')->label('تعداد مقاله')->counts('articles'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategories::route('/'),
        ];
    }
}
