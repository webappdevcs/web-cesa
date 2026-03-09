<?php

namespace Cesa\Rekrutmen\Filament\Resources\JobApplicationResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class HistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('rekrutmen::app.relation_managers.histories.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                Tables\Columns\TextColumn::make('fromStage.name')
                    ->label(__('rekrutmen::app.relation_managers.histories.columns.from_stage'))
                    ->placeholder(__('rekrutmen::app.relation_managers.histories.placeholders.from_stage')),
                Tables\Columns\TextColumn::make('toStage.name')
                    ->label(__('rekrutmen::app.relation_managers.histories.columns.to_stage'))
                    ->placeholder(__('rekrutmen::app.relation_managers.histories.placeholders.to_stage')),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('rekrutmen::app.relation_managers.histories.columns.status'))
                    ->badge(),
                Tables\Columns\TextColumn::make('notes')
                    ->label(__('rekrutmen::app.relation_managers.histories.columns.notes'))
                    ->limit(50),
                Tables\Columns\TextColumn::make('performer.name')
                    ->label(__('rekrutmen::app.relation_managers.histories.columns.performed_by')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('rekrutmen::app.relation_managers.histories.columns.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Readonly, records created via actions
            ])
            ->actions([
                // Readonly
            ])
            ->bulkActions([
                // Readonly
            ]);
    }
}
