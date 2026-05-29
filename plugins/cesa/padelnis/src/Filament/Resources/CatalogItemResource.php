<?php

namespace Cesa\Padelnis\Filament\Resources;

use Cesa\Padelnis\Enums\PricingMode;
use Cesa\Padelnis\Filament\Clusters\Configurations;
use Cesa\Padelnis\Filament\Resources\CatalogItemResource\Pages;
use Cesa\Padelnis\Models\CatalogItem;
use Cesa\Padelnis\Models\Reservation;
use Cesa\Padelnis\Models\TransactionType as TransactionTypeMaster;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Webkul\PluginManager\Package;
use Webkul\Security\Traits\HasResourcePermissionQuery;

class CatalogItemResource extends Resource
{
    use HasResourcePermissionQuery;

    protected static ?string $model = CatalogItem::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 30;

    protected static ?string $cluster = Configurations::class;

    public static function shouldRegisterNavigation(): bool
    {
        return Package::isPluginInstalled('padelnis');
    }

    public static function getNavigationLabel(): string
    {
        return __('padelnis::filament/resources/catalog-item.navigation.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.padelnis');
    }

    public static function getModelLabel(): string
    {
        return __('padelnis::filament/resources/catalog-item.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('padelnis::filament/resources/catalog-item.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('transaction_type')
                    ->label(__('padelnis::filament/resources/catalog-item.fields.transaction_type'))
                    ->options(fn (): array => Reservation::transactionTypeOptions())
                    ->default('regular')
                    ->required()
                    ->live()
                    ->columnSpan(2),
                Select::make('time_slot')
                    ->label(__('padelnis::filament/resources/catalog-item.fields.time_slot'))
                    ->options(fn (): array => static::slotOptions())
                    ->placeholder(__('padelnis::filament/resources/catalog-item.placeholders.all_slots'))
                    ->searchable()
                    ->visible(fn (Get $get): bool => static::usesTimedResources($get))
                    ->dehydrated(fn (Get $get): bool => static::usesTimedResources($get))
                    ->columnSpan(2),
                TextInput::make('name')
                    ->label(__('padelnis::filament/resources/catalog-item.fields.name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),
                Textarea::make('description')
                    ->label(__('padelnis::filament/resources/catalog-item.fields.description'))
                    ->rows(3)
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Toggle::make('requires_court')
                    ->label(__('padelnis::filament/resources/catalog-item.fields.requires_court'))
                    ->helperText('Aktifkan jika layanan ini menyewa/menggunakan lapangan.')
                    ->default(true)
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get): void {
                        static::syncRequirementFields($set, $get);
                    })
                    ->columnSpan(1),
                Toggle::make('requires_coach')
                    ->label(__('padelnis::filament/resources/catalog-item.fields.requires_coach'))
                    ->helperText('Aktifkan jika layanan ini menggunakan pendampingan coach.')
                    ->default(false)
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get): void {
                        static::syncRequirementFields($set, $get);
                    })
                    ->columnSpan(1),
                Select::make('pricing_mode')
                    ->label(__('padelnis::filament/resources/catalog-item.fields.pricing_mode'))
                    ->options(PricingMode::options())
                    ->default(PricingMode::PerSlot->value)
                    ->required()
                    ->live()
                    ->columnSpan(2),
                TextInput::make('price_amount')
                    ->label(__('padelnis::filament/resources/catalog-item.fields.price_amount'))
                    ->inputMode('numeric')
                    ->prefix('Rp')
                    ->required()
                    ->rule('numeric')
                    ->rule('min:0')
                    ->formatStateUsing(fn (mixed $state): ?string => Reservation::formatTransferAmountForForm($state))
                    ->mutateStateForValidationUsing(fn (mixed $state): ?string => Reservation::normalizeTransferAmount($state))
                    ->dehydrateStateUsing(fn (mixed $state): ?string => Reservation::normalizeTransferAmount($state))
                    ->columnSpan(2),
                Toggle::make('uses_component_pricing')
                    ->label(__('padelnis::filament/resources/catalog-item.fields.uses_component_pricing'))
                    ->helperText(__('padelnis::filament/resources/catalog-item.helpers.uses_component_pricing'))
                    ->dehydrated(false)
                    ->visible(fn (Get $get): bool => static::usesTimedResources($get))
                    ->afterStateHydrated(function (Toggle $component, ?CatalogItem $record = null): void {
                        $component->state($record?->hasPriceComponents() ?? false);
                    })
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, mixed $state): void {
                        if (! $state) {
                            $set('price_components', null);

                            return;
                        }

                        static::syncPriceComponentsWithRequirements($set, $get);
                    })
                    ->columnSpan(2),
                Repeater::make('price_components')
                    ->label(__('padelnis::filament/resources/catalog-item.fields.price_components'))
                    ->helperText('Atur alokasi harga dasar ke masing-masing komponen. Komponen otomatis disesuaikan berdasarkan pilihan \'Butuh Lapangan\' dan \'Butuh Coach\'.')
                    ->schema([
                        Select::make('component')
                            ->label(__('padelnis::filament/resources/catalog-item.price_components.fields.component'))
                            ->options(CatalogItem::priceComponentOptions())
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),
                        Toggle::make('is_commissionable')
                            ->label(__('padelnis::filament/resources/catalog-item.price_components.fields.is_commissionable'))
                            ->default(false)
                            ->visible(fn (Get $get): bool => $get('component') === CatalogItem::PRICE_COMPONENT_COACH)
                            ->dehydrateStateUsing(fn (mixed $state, Get $get): bool => $get('component') === CatalogItem::PRICE_COMPONENT_COACH && (bool) $state)
                            ->columnSpan(1),
                        Select::make('calculation_type')
                            ->label(__('padelnis::filament/resources/catalog-item.price_components.fields.calculation_type'))
                            ->options(CatalogItem::priceComponentCalculationOptions())
                            ->placeholder(__('padelnis::filament/resources/catalog-item.price_components.summary.priced_by_rules'))
                            ->helperText('Nominal Tetap: biaya tetap. Persentase: persen dari harga total. Pakai Harga Layanan: mengambil harga dari sewa lapangan lain. Kosongkan jika ingin diatur lewat menu Aturan Harga.')
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                if (blank($state)) {
                                    $set('amount', null);
                                    $set('percentage', null);
                                    $set('basis', CatalogItem::BASIS_QUOTED_AMOUNT);
                                    $set('source_catalog_item_id', null);
                                } elseif ($state === CatalogItem::CALCULATION_FIXED) {
                                    $set('percentage', null);
                                    $set('source_catalog_item_id', null);
                                } elseif ($state === CatalogItem::CALCULATION_PERCENTAGE) {
                                    $set('amount', null);
                                    $set('source_catalog_item_id', null);
                                } elseif ($state === CatalogItem::CALCULATION_CATALOG_ITEM) {
                                    $set('amount', null);
                                    $set('percentage', null);
                                }
                            })
                            ->columnSpan(2),
                        TextInput::make('amount')
                            ->label(__('padelnis::filament/resources/catalog-item.price_components.fields.amount'))
                            ->inputMode('numeric')
                            ->prefix('Rp')
                            ->required(fn (Get $get): bool => $get('calculation_type') === CatalogItem::CALCULATION_FIXED)
                            ->visible(fn (Get $get): bool => $get('calculation_type') === CatalogItem::CALCULATION_FIXED)
                            ->rule('numeric')
                            ->rule('min:0')
                            ->formatStateUsing(fn (mixed $state): ?string => Reservation::formatTransferAmountForForm($state))
                            ->mutateStateForValidationUsing(fn (mixed $state): ?string => Reservation::normalizeTransferAmount($state))
                            ->dehydrateStateUsing(fn (mixed $state): ?string => Reservation::normalizeTransferAmount($state))
                            ->columnSpan(2),
                        TextInput::make('percentage')
                            ->label(__('padelnis::filament/resources/catalog-item.price_components.fields.percentage'))
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(fn (Get $get): bool => $get('calculation_type') === CatalogItem::CALCULATION_PERCENTAGE)
                            ->visible(fn (Get $get): bool => $get('calculation_type') === CatalogItem::CALCULATION_PERCENTAGE)
                            ->columnSpan(2),
                        Select::make('basis')
                            ->label(__('padelnis::filament/resources/catalog-item.price_components.fields.basis'))
                            ->options(CatalogItem::priceComponentBasisOptions())
                            ->default(CatalogItem::BASIS_QUOTED_AMOUNT)
                            ->required(fn (Get $get): bool => $get('calculation_type') === CatalogItem::CALCULATION_PERCENTAGE)
                            ->visible(fn (Get $get): bool => $get('calculation_type') === CatalogItem::CALCULATION_PERCENTAGE)
                            ->columnSpan(2),
                        Select::make('source_catalog_item_id')
                            ->label(__('padelnis::filament/resources/catalog-item.price_components.fields.source_catalog_item'))
                            ->options(fn (Get $get): array => collect(CatalogItem::options())->except($get('../../id'))->all())
                            ->searchable()
                            ->required(fn (Get $get): bool => $get('calculation_type') === CatalogItem::CALCULATION_CATALOG_ITEM)
                            ->visible(fn (Get $get): bool => $get('calculation_type') === CatalogItem::CALCULATION_CATALOG_ITEM)
                            ->columnSpan(2),
                    ])
                    ->columns(2)
                    ->default(fn (Get $get): array => CatalogItem::normalizePriceComponents(
                        [],
                        (bool) $get('requires_court'),
                        (bool) $get('requires_coach'),
                    ))
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->visible(fn (Get $get): bool => static::usesComponentPricing($get))
                    ->dehydratedWhenHidden()
                    ->dehydrateStateUsing(fn (mixed $state, Get $get): ?array => static::priceComponentsForPersistence(
                        $state,
                        static::usesComponentPricing($get),
                        (bool) $get('requires_court'),
                        (bool) $get('requires_coach'),
                    ))
                    ->collapsible()
                    ->columnSpanFull(),
                TextInput::make('duration_hours')
                    ->label(__('padelnis::filament/resources/catalog-item.fields.duration_hours'))
                    ->numeric()
                    ->minValue(1)
                    ->columnSpan(2),
                TextInput::make('session_count')
                    ->label(__('padelnis::filament/resources/catalog-item.fields.session_count'))
                    ->numeric()
                    ->minValue(1)
                    ->columnSpan(2),
                Toggle::make('is_active')
                    ->label(__('padelnis::filament/resources/catalog-item.fields.is_active'))
                    ->default(true)
                    ->columnSpan(1),
                Toggle::make('allow_public_booking')
                    ->label(__('padelnis::filament/resources/catalog-item.fields.allow_public_booking'))
                    ->default(true)
                    ->columnSpan(1),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('transaction_type')
                    ->label(__('padelnis::filament/resources/catalog-item.table.columns.transaction_type'))
                    ->state(fn (CatalogItem $record): string => TransactionTypeMaster::labelFor($record->transaction_type))
                    ->badge()
                    ->sortable(),
                TextColumn::make('pricing_mode')
                    ->label(__('padelnis::filament/resources/catalog-item.table.columns.pricing_mode'))
                    ->state(fn (CatalogItem $record): string => PricingMode::fromValue($record->pricing_mode)->label())
                    ->badge()
                    ->sortable(),
                TextColumn::make('time_slot')
                    ->label(__('padelnis::filament/resources/catalog-item.table.columns.time_slot'))
                    ->placeholder(__('padelnis::filament/resources/catalog-item.placeholders.all_slots'))
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('padelnis::filament/resources/catalog-item.table.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price_amount')
                    ->label(__('padelnis::filament/resources/catalog-item.table.columns.price_amount'))
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('price_components')
                    ->label(__('padelnis::filament/resources/catalog-item.table.columns.price_components'))
                    ->state(fn (CatalogItem $record): ?string => $record->priceComponentSummary())
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('duration_hours')
                    ->label(__('padelnis::filament/resources/catalog-item.table.columns.duration_hours'))
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('session_count')
                    ->label(__('padelnis::filament/resources/catalog-item.table.columns.session_count'))
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('requires_court')
                    ->label(__('padelnis::filament/resources/catalog-item.table.columns.requires_court'))
                    ->state(fn (CatalogItem $record): string => static::booleanLabel($record->requiresCourt()))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('requires_coach')
                    ->label(__('padelnis::filament/resources/catalog-item.table.columns.requires_coach'))
                    ->state(fn (CatalogItem $record): string => static::booleanLabel($record->requiresCoach()))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('allow_public_booking')
                    ->label(__('padelnis::filament/resources/catalog-item.table.columns.allow_public_booking'))
                    ->state(fn (CatalogItem $record): string => static::booleanLabel((bool) $record->allow_public_booking))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('is_active')
                    ->label(__('padelnis::filament/resources/catalog-item.table.columns.is_active'))
                    ->state(fn (CatalogItem $record): string => $record->is_active
                        ? __('padelnis::filament/resources/catalog-item.status.active')
                        : __('padelnis::filament/resources/catalog-item.status.inactive'))
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('transaction_type')
                    ->label(__('padelnis::filament/resources/catalog-item.filters.transaction_type'))
                    ->options(fn (): array => Reservation::transactionTypeOptions()),
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
            'index' => Pages\ManageCatalogItems::route('/'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function slotOptions(): array
    {
        $slots = array_values(config('padelnis.slots', []));

        return array_combine($slots, $slots) ?: [];
    }

    protected static function syncPriceComponentsWithRequirements(Set $set, Get $get): void
    {
        if (! static::usesComponentPricing($get)) {
            $set('price_components', null);

            return;
        }

        $set('price_components', static::priceComponentsForPersistence(
            $get('price_components'),
            true,
            (bool) $get('requires_court'),
            (bool) $get('requires_coach'),
        ));
    }

    protected static function syncRequirementFields(Set $set, Get $get): void
    {
        static::syncPriceComponentsWithRequirements($set, $get);

        if (! static::usesTimedResources($get)) {
            $set('time_slot', null);
        }
    }

    protected static function usesTimedResources(Get $get): bool
    {
        return (bool) $get('requires_court') || (bool) $get('requires_coach');
    }

    protected static function usesComponentPricing(Get $get): bool
    {
        return static::usesTimedResources($get) && (bool) $get('uses_component_pricing');
    }

    /**
     * @return list<array{component: string, calculation_type: ?string, amount: ?string, percentage: ?float, basis: string, source_catalog_item_id: ?int, is_commissionable: bool}>|null
     */
    protected static function priceComponentsForPersistence(mixed $state, bool $usesComponentPricing, bool $requiresCourt, bool $requiresCoach): ?array
    {
        if (! $usesComponentPricing || (! $requiresCourt && ! $requiresCoach)) {
            return null;
        }

        return CatalogItem::normalizePriceComponents($state, $requiresCourt, $requiresCoach);
    }

    protected static function booleanLabel(bool $value): string
    {
        return $value
            ? __('padelnis::filament/resources/catalog-item.status.yes')
            : __('padelnis::filament/resources/catalog-item.status.no');
    }
}
