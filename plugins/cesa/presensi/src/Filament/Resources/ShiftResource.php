<?php

namespace Cesa\Presensi\Filament\Resources;

use Cesa\Presensi\Filament\Clusters\Configurations;
use Cesa\Presensi\Filament\Resources\ShiftResource\Pages;
use Cesa\Presensi\Models\Shift;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Configurations::class;

    public static function getNavigationLabel(): string
    {
        return __('presensi::app.resources.shift.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('presensi::app.resources.shift.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('presensi::app.resources.shift.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label(__('presensi::app.resources.shift.form.fields.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TimePicker::make('start_time')
                    ->label(__('presensi::app.resources.shift.form.fields.start_time'))
                    ->required(),
                Forms\Components\TimePicker::make('end_time')
                    ->label(__('presensi::app.resources.shift.form.fields.end_time'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('presensi::app.resources.shift.table.columns.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label(__('presensi::app.resources.shift.table.columns.start_time')),
                Tables\Columns\TextColumn::make('end_time')
                    ->label(__('presensi::app.resources.shift.table.columns.end_time')),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\EditAction::make()
                    ->modal()
                    ->slideOver()
                    ->modalWidth('md')
                    ->schema(fn (Schema $schema): Schema => static::form($schema->columns(1))),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShifts::route('/'),
        ];
    }
}
