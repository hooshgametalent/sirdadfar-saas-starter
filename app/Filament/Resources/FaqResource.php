<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages\ManageFaqs;
use App\Models\Faq;
use App\Providers\Filament\AdminPanelProvider;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = AdminPanelProvider::GROUP_CONTENT;

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'پرسش متداول';

    protected static ?string $pluralModelLabel = 'پرسش‌های متداول';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('question')->label('پرسش')->required()->maxLength(500),
                Textarea::make('answer')->label('پاسخ')->required()->rows(5)->maxLength(5000),
                TextInput::make('sort_order')->label('ترتیب نمایش')->numeric()->minValue(0)->default(0),
                Toggle::make('is_published')->label('منتشر شود')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                TextColumn::make('question')->label('پرسش')->searchable()->limit(80)->wrap(),
                TextColumn::make('sort_order')->label('ترتیب'),
                IconColumn::make('is_published')->label('منتشر شده')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFaqs::route('/'),
        ];
    }
}
