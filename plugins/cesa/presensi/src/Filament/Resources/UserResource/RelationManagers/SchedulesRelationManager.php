<?php

namespace Cesa\Presensi\Filament\Resources\UserResource\RelationManagers;

use Cesa\Presensi\Filament\Resources\ScheduleResource;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('presensi::app.relation_managers.schedules.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('office_id')
                    ->label(__('presensi::app.relation_managers.schedules.form.fields.office_id'))
                    ->relationship('office', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('shift_id')
                    ->label(__('presensi::app.relation_managers.schedules.form.fields.shift_id'))
                    ->relationship('shift', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\Toggle::make('is_wfa')
                    ->label(__('presensi::app.relation_managers.schedules.form.fields.is_wfa')),

                Forms\Components\Toggle::make('is_banned')
                    ->label(__('presensi::app.relation_managers.schedules.form.fields.is_banned'))
                    ->hidden(fn (): bool => ! ScheduleResource::userCan('update_presensi_schedule')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('office.name')
                    ->label(__('presensi::app.relation_managers.schedules.table.columns.office_name'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('shift.name')
                    ->label(__('presensi::app.relation_managers.schedules.table.columns.shift_name'))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_wfa')
                    ->label(__('presensi::app.relation_managers.schedules.table.columns.is_wfa'))
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_banned')
                    ->label(__('presensi::app.relation_managers.schedules.table.columns.is_banned'))
                    ->boolean()
                    ->visible(fn (): bool => ScheduleResource::userCan('update_presensi_schedule')),
            ])
            ->filters([
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make()->icon('heroicon-o-plus-circle')
                    ->modal()
                    ->slideOver()
                    ->modalWidth('md')
                    ->schema(fn (Schema $schema): Schema => $this->form($schema->columns(1))),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()
                    ->modal()
                    ->slideOver()
                    ->modalWidth('md')
                    ->schema(fn (Schema $schema): Schema => $this->form($schema->columns(1))),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
