<?php

namespace Cesa\Helpdesk\Filament\Clusters\Configurations\Resources;

use BackedEnum;
use Cesa\Helpdesk\Filament\Clusters\Configurations;
use Cesa\Helpdesk\Models\Priority;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class PriorityResource extends Resource
{
    protected static ?string $model = Priority::class;

    protected static BackedEnum|string|null $navigationIcon = null;

    protected static ?string $cluster = Configurations::class;

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('helpdesk::app.resources.priority.plural');
    }

    public static function getPluralModelLabel(): string
    {
        return __('helpdesk::app.resources.priority.plural');
    }

    public static function getModelLabel(): string
    {
        return __('helpdesk::app.resources.priority.single');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tickets_count')
                    ->counts('tickets')
                    ->label('Tickets')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()->slideOver()->modalWidth('md'),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => \Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\PriorityResource\Pages\ListPriorities::route('/'),
        ];
    }
}
