<?php

namespace Cesa\ExitClearance\Filament\Clusters\Configurations\Resources;

use BackedEnum;
use Cesa\ExitClearance\Filament\Clusters\Configurations;
use Cesa\ExitClearance\Models\Approver;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ApproverResource extends Resource
{
    protected static ?string $model = Approver::class;

    protected static BackedEnum|string|null $navigationIcon = null;

    protected static ?string $cluster = Configurations::class;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return trans_choice('exit-clearance::app.resources.approver', 2);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('exit-clearance::app.resources.approver', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('exit-clearance::app.resources.approver', 1);
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(__('exit-clearance::app.form.approver.name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label(__('exit-clearance::app.form.approver.email'))
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label(__('exit-clearance::app.form.approver.phone'))
                    ->tel()
                    ->maxLength(255),
                TextInput::make('title')
                    ->label(__('exit-clearance::app.form.approver.title'))
                    ->required()
                    ->maxLength(255),
                Select::make('departments')
                    ->label(__('exit-clearance::app.form.approver.departments'))
                    ->relationship('departments', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('exit-clearance::app.form.approver.name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('exit-clearance::app.form.approver.email'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label(__('exit-clearance::app.form.approver.phone'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('exit-clearance::app.form.approver.title'))
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()->slideOver()->modalWidth('md'),
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
            'index'  => \Cesa\ExitClearance\Filament\Clusters\Configurations\Resources\ApproverResource\Pages\ListApprovers::route('/'),
        ];
    }
}
