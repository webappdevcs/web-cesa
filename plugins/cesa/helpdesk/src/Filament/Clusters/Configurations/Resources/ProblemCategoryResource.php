<?php

namespace Cesa\Helpdesk\Filament\Clusters\Configurations\Resources;

use BackedEnum;
use Cesa\Helpdesk\Filament\Clusters\Configurations;
use Cesa\Helpdesk\Models\ProblemCategory;
use Cesa\Helpdesk\Models\Unit;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProblemCategoryResource extends Resource
{
    protected static ?string $model = ProblemCategory::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $cluster = Configurations::class;

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('helpdesk::app.resources.problem_category.plural');
    }

    public static function getPluralModelLabel(): string
    {
        return __('helpdesk::app.resources.problem_category.plural');
    }

    public static function getModelLabel(): string
    {
        return __('helpdesk::app.resources.problem_category.single');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('unit_id')
                ->label('Unit')
                ->relationship('unit', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->columnSpanFull()
                ->live(),
            TextInput::make('name')
                ->columnSpanFull()
                ->required()
                ->maxLength(255),
            Select::make('default_responsible_id')
                ->label('Default Responsible')
                ->options(function (Get $get): array {
                    $unitId = $get('unit_id');

                    if (! $unitId) {
                        return [];
                    }

                    return Unit::query()
                        ->find($unitId)?->users()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'users.id')
                        ->all() ?? [];
                })
                ->searchable()
                ->columnSpanFull()
                ->preload(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Unit')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('defaultResponsible.name')
                    ->label('Default Responsible')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()->slideOver()->modalWidth('md'),
                DeleteAction::make()->slideOver()->modalWidth('md'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()->slideOver()->modalWidth('md'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => \Cesa\Helpdesk\Filament\Clusters\Configurations\Resources\ProblemCategoryResource\Pages\ListProblemCategories::route('/'),
        ];
    }
}
