<?php

namespace Cesa\Helpdesk\Filament\Clusters\Configurations\Resources;

use BackedEnum;
use Cesa\Helpdesk\Filament\Clusters\Configurations;
use Cesa\Helpdesk\Models\Unit;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Webkul\Security\Models\User;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static BackedEnum|string|null $navigationIcon = null;

    protected static ?string $cluster = Configurations::class;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('helpdesk::app.resources.unit.plural');
    }

    public static function getPluralModelLabel(): string
    {
        return __('helpdesk::app.resources.unit.plural');
    }

    public static function getModelLabel(): string
    {
        return __('helpdesk::app.resources.unit.single');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->rows(3)
                ->columnSpanFull(),
            Select::make('users')
                ->label('Assigned Users')
                ->relationship('users', 'name')
                ->multiple()
                ->preload()
                ->searchable()
                ->options(User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all()),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users')
                    ->sortable(),
                Tables\Columns\TextColumn::make('problem_categories_count')
                    ->counts('problemCategories')
                    ->label('Categories')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()->slideOver()->modalWidth('lg'),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => \Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\UnitResource\Pages\ListUnits::route('/'),
        ];
    }
}
