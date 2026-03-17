<?php

namespace Cesa\Shelf\Filament\Resources;

use Cesa\Shelf\Filament\Clusters\Configurations;
use Cesa\Shelf\Filament\Resources\CategoryResource\Pages;
use Cesa\Shelf\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoryResource extends ShelfResource
{
    protected static ?string $model = Category::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?string $cluster = Configurations::class;

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->translateLabel()
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('parent_id')
                    ->translateLabel()
                    ->options(Category::whereNull('parent_id')->pluck('name', 'id'))
                    ->nullable()
                    ->searchable()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->translateLabel()->sortable()->searchable(),
                TextColumn::make('created_at')->translateLabel()->dateTime()->sortable(),
                TextColumn::make('updated_at')->translateLabel()->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()->slideOver()->modalWidth('md'),
                DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCategories::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('parent_id');
    }
}
