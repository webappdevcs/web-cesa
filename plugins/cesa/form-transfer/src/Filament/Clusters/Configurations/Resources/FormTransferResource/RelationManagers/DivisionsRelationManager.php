<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\FormTransferResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class DivisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'divisions';

    protected static ?string $title = 'Divisions';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('form-transfer::app.relation_managers.divisions');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('form-transfer::app.config.divisions.fields.name'))
                ->required()
                ->maxLength(191),
            Toggle::make('is_active')
                ->label(__('form-transfer::app.config.divisions.fields.is_active'))
                ->default(true),
            Textarea::make('description')
                ->label(__('form-transfer::app.config.divisions.fields.description'))
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make()->slideOver()
                    ->modalHeading(__('form-transfer::app.config.divisions.navigation.label')),
            ])
            ->columns([
                TextColumn::make('name')
                    ->label(__('form-transfer::app.config.divisions.columns.name'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('form-transfer::app.config.divisions.columns.is_active'))
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()->slideOver(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
                RestoreBulkAction::make(),
                ForceDeleteBulkAction::make(),
            ])
            ->filters([
                TrashedFilter::make(),
            ]);
    }
}
