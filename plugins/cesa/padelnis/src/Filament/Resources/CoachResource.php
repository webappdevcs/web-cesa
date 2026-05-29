<?php

namespace Cesa\Padelnis\Filament\Resources;

use Cesa\Padelnis\Filament\Clusters\Configurations;
use Cesa\Padelnis\Filament\Resources\CoachResource\Pages;
use Cesa\Padelnis\Models\Coach;
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

class CoachResource extends Resource
{
    use HasResourcePermissionQuery;

    protected static ?string $model = Coach::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 20;

    protected static ?string $cluster = Configurations::class;

    public static function shouldRegisterNavigation(): bool
    {
        return Package::isPluginInstalled('padelnis');
    }

    public static function getNavigationLabel(): string
    {
        return __('padelnis::filament/resources/coach.navigation.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.padelnis');
    }

    public static function getModelLabel(): string
    {
        return __('padelnis::filament/resources/coach.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('padelnis::filament/resources/coach.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('padelnis::filament/resources/coach.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),
                TextInput::make('phone')
                    ->label(__('padelnis::filament/resources/coach.fields.phone'))
                    ->tel()
                    ->maxLength(255)
                    ->columnSpan(2),
                Toggle::make('is_active')
                    ->label(__('padelnis::filament/resources/coach.fields.is_active'))
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
                    ->label(__('padelnis::filament/resources/coach.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label(__('padelnis::filament/resources/coach.table.columns.phone'))
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('is_active')
                    ->label(__('padelnis::filament/resources/coach.table.columns.is_active'))
                    ->state(fn (Coach $record): string => $record->is_active
                        ? __('padelnis::filament/resources/coach.status.active')
                        : __('padelnis::filament/resources/coach.status.inactive'))
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
            'index' => Pages\ManageCoaches::route('/'),
        ];
    }
}
