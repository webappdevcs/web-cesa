<?php

namespace Cesa\Padelnis\Filament\Resources;

use Cesa\Padelnis\Filament\Clusters\Configurations;
use Cesa\Padelnis\Filament\Resources\TransactionTypeResource\Pages;
use Cesa\Padelnis\Models\TransactionType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\Security\Traits\HasResourcePermissionQuery;

class TransactionTypeResource extends Resource
{
    use HasResourcePermissionQuery;

    protected static ?string $model = TransactionType::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = Configurations::class;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return __('padelnis::filament/resources/transaction-type.navigation.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.padelnis');
    }

    public static function getModelLabel(): string
    {
        return __('padelnis::filament/resources/transaction-type.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('padelnis::filament/resources/transaction-type.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label(__('padelnis::filament/resources/transaction-type.fields.code'))
                    ->required()
                    ->maxLength(20)
                    ->rule('alpha_dash:ascii')
                    ->helperText(__('padelnis::filament/resources/transaction-type.helpers.code'))
                    ->disabled(fn (?TransactionType $record): bool => $record !== null)
                    ->dehydrated(fn (?TransactionType $record): bool => $record === null)
                    ->columnSpan(2),
                TextInput::make('name')
                    ->label(__('padelnis::filament/resources/transaction-type.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),
                Toggle::make('requires_coach')
                    ->label(__('padelnis::filament/resources/transaction-type.fields.requires_coach'))
                    ->default(false)
                    ->columnSpan(2),
                Toggle::make('requires_catalog_item')
                    ->label(__('padelnis::filament/resources/transaction-type.fields.requires_catalog_item'))
                    ->default(false)
                    ->columnSpan(2),
                Toggle::make('is_active')
                    ->label(__('padelnis::filament/resources/transaction-type.fields.is_active'))
                    ->default(true)
                    ->columnSpan(2),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('name')
                    ->label(__('padelnis::filament/resources/transaction-type.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label(__('padelnis::filament/resources/transaction-type.table.columns.code'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('requires_coach')
                    ->label(__('padelnis::filament/resources/transaction-type.table.columns.requires_coach'))
                    ->state(fn (TransactionType $record): string => static::booleanLabel($record->requires_coach))
                    ->badge(),
                TextColumn::make('requires_catalog_item')
                    ->label(__('padelnis::filament/resources/transaction-type.table.columns.requires_catalog_item'))
                    ->state(fn (TransactionType $record): string => static::booleanLabel($record->requires_catalog_item))
                    ->badge(),
                TextColumn::make('is_active')
                    ->label(__('padelnis::filament/resources/transaction-type.table.columns.is_active'))
                    ->state(fn (TransactionType $record): string => $record->is_active
                        ? __('padelnis::filament/resources/transaction-type.status.active')
                        : __('padelnis::filament/resources/transaction-type.status.inactive'))
                    ->badge()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()->slideOver()->modalWidth('md'),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTransactionTypes::route('/'),
        ];
    }

    protected static function booleanLabel(bool $value): string
    {
        return $value
            ? __('padelnis::filament/resources/transaction-type.status.yes')
            : __('padelnis::filament/resources/transaction-type.status.no');
    }
}
