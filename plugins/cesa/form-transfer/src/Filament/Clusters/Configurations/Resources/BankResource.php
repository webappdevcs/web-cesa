<?php

namespace Cesa\FormTransfer\Filament\Clusters\Configurations\Resources;

use Cesa\FormTransfer\Filament\Clusters\Configurations;
use Cesa\FormTransfer\Filament\Clusters\Configurations\Resources\BankResource\Pages;
use Cesa\FormTransfer\Models\TransferBank;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class BankResource extends Resource
{
    protected static ?string $model = TransferBank::class;

    protected static ?string $cluster = Configurations::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('form-transfer::app.config.banks.navigation.label');
    }

    public static function getNavigationGroup(): string
    {
        return __('form-transfer::app.config.banks.navigation.group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('form-transfer::app.config.banks.fields.code'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(10)
                    ->helperText(__('form-transfer::app.config.banks.fields.code_hint')),
                TextInput::make('name')
                    ->label(__('form-transfer::app.config.banks.fields.name'))
                    ->maxLength(191)
                    ->required(),
                TextInput::make('short_name')
                    ->label(__('form-transfer::app.config.banks.fields.short_name'))
                    ->maxLength(50)
                    ->helperText(__('form-transfer::app.config.banks.fields.short_name_hint')),
                TextInput::make('sort_order')
                    ->label(__('form-transfer::app.config.banks.fields.sort_order'))
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label(__('form-transfer::app.config.banks.fields.is_active'))
                    ->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('form-transfer::app.config.banks.columns.code'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('name')
                    ->label(__('form-transfer::app.config.banks.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('short_name')
                    ->label(__('form-transfer::app.config.banks.columns.short_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label(__('form-transfer::app.config.banks.columns.sort_order'))
                    ->sortable()
                    ->alignCenter(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('form-transfer::app.config.banks.columns.is_active'))
                    ->sortable()
                    ->alignCenter(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('form-transfer::app.config.banks.filters.is_active')),
                TrashedFilter::make(),
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBanks::route('/'),
        ];
    }
}
