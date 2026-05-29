<?php

namespace Cesa\Padelnis\Filament\Resources;

use Cesa\Padelnis\Filament\Clusters\Configurations;
use Cesa\Padelnis\Filament\Resources\CourtResource\Pages;
use Cesa\Padelnis\Models\Court;
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
use Webkul\PluginManager\Package;
use Webkul\Security\Traits\HasResourcePermissionQuery;

class CourtResource extends Resource
{
    use HasResourcePermissionQuery;

    protected static ?string $model = Court::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = Configurations::class;

    public static function shouldRegisterNavigation(): bool
    {
        return Package::isPluginInstalled('padelnis');
    }

    public static function getNavigationLabel(): string
    {
        return __('padelnis::filament/resources/court.navigation.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.padelnis');
    }

    public static function getModelLabel(): string
    {
        return __('padelnis::filament/resources/court.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('padelnis::filament/resources/court.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('padelnis::filament/resources/court.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),
                TextInput::make('court_type')
                    ->label(__('padelnis::filament/resources/court.fields.court_type'))
                    ->maxLength(255)
                    ->columnSpan(2),
                Toggle::make('is_active')
                    ->label(__('padelnis::filament/resources/court.fields.is_active'))
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
                    ->label(__('padelnis::filament/resources/court.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('court_type')
                    ->label(__('padelnis::filament/resources/court.table.columns.court_type'))
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('is_active')
                    ->label(__('padelnis::filament/resources/court.table.columns.is_active'))
                    ->state(fn (Court $record): string => $record->is_active
                        ? __('padelnis::filament/resources/court.status.active')
                        : __('padelnis::filament/resources/court.status.inactive'))
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
            'index' => Pages\ManageCourts::route('/'),
        ];
    }
}
