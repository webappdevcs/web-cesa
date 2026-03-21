<?php

namespace Cesa\Presensi\Filament\Resources;

use Cesa\Presensi\Filament\Clusters\Configurations;
use Cesa\Presensi\Filament\Resources\OfficeResource\Pages;
use Cesa\Presensi\Models\Office;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Configurations::class;

    public static function getNavigationLabel(): string
    {
        return __('presensi::app.resources.office.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('presensi::app.resources.office.model.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('presensi::app.resources.office.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label(__('presensi::app.resources.office.form.fields.name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('latitude')
                    ->label(__('presensi::app.resources.office.form.fields.latitude'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('longitude')
                    ->label(__('presensi::app.resources.office.form.fields.longitude'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('radius')
                    ->label(__('presensi::app.resources.office.form.fields.radius'))
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('presensi::app.resources.office.table.columns.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('latitude')
                    ->label(__('presensi::app.resources.office.table.columns.latitude'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('longitude')
                    ->label(__('presensi::app.resources.office.table.columns.longitude'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('radius')
                    ->label(__('presensi::app.resources.office.table.columns.radius'))
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListOffices::route('/'),
        ];
    }
}
