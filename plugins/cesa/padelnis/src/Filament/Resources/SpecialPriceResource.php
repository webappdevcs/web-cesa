<?php

namespace Cesa\Padelnis\Filament\Resources;

use Cesa\Padelnis\Filament\Clusters\Configurations;
use Cesa\Padelnis\Filament\Resources\SpecialPriceResource\Pages;
use Cesa\Padelnis\Models\CatalogItem;
use Cesa\Padelnis\Models\Coach;
use Cesa\Padelnis\Models\Court;
use Cesa\Padelnis\Models\Reservation;
use Cesa\Padelnis\Models\SpecialPrice;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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

class SpecialPriceResource extends Resource
{
    use HasResourcePermissionQuery;

    protected static ?string $model = SpecialPrice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 40;

    protected static ?string $cluster = Configurations::class;

    public static function shouldRegisterNavigation(): bool
    {
        return Package::isPluginInstalled('padelnis');
    }

    public static function getNavigationLabel(): string
    {
        return __('padelnis::filament/resources/special-price.navigation.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.padelnis');
    }

    public static function getModelLabel(): string
    {
        return __('padelnis::filament/resources/special-price.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('padelnis::filament/resources/special-price.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('catalog_item_id')
                    ->label(__('padelnis::filament/resources/special-price.fields.catalog_item'))
                    ->options(fn (): array => CatalogItem::options())
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                        $set('component', null);
                        $set('calculation_type', CatalogItem::CALCULATION_FIXED);
                        $set('percentage', null);

                        if (! static::catalogItemRequiresCourt($state)) {
                            $set('court_type', null);
                            $set('court_id', null);
                        }

                        if (! static::catalogItemRequiresCoach($state)) {
                            $set('coach_id', null);
                        }

                        if (! static::catalogItemUsesTimedResources($state)) {
                            $set('time_slot', null);
                        }
                    })
                    ->columnSpan(2),
                Select::make('component')
                    ->label(__('padelnis::filament/resources/special-price.fields.component'))
                    ->options(fn (Get $get): array => static::componentOptionsForCatalogItem($get('catalog_item_id')))
                    ->placeholder(__('padelnis::filament/resources/special-price.placeholders.total_price'))
                    ->helperText('Pilih komponen spesifik jika ingin mengubah tarif hanya untuk lapangan/coach. Biarkan kosong (Total layanan) jika ingin mengubah total harga paket.')
                    ->searchable()
                    ->live()
                    ->visible(fn (Get $get): bool => static::catalogItemUsesComponents($get('catalog_item_id')))
                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                        if (blank($state)) {
                            $set('calculation_type', CatalogItem::CALCULATION_FIXED);
                            $set('percentage', null);
                            $set('basis', CatalogItem::BASIS_QUOTED_AMOUNT);
                        }
                    })
                    ->columnSpan(2),
                TextInput::make('name')
                    ->label(__('padelnis::filament/resources/special-price.fields.name'))
                    ->maxLength(255)
                    ->columnSpan(2),
                Select::make('calculation_type')
                    ->label(__('padelnis::filament/resources/special-price.fields.calculation_type'))
                    ->options(fn (Get $get): array => static::calculationTypeOptions($get('component')))
                    ->default(CatalogItem::CALCULATION_FIXED)
                    ->required()
                    ->live()
                    ->visible(fn (Get $get): bool => filled($get('component')))
                    ->dehydrateStateUsing(fn (mixed $state, Get $get): string => blank($get('component'))
                        ? CatalogItem::CALCULATION_FIXED
                        : ($state ?: CatalogItem::CALCULATION_FIXED))
                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                        if ($state === CatalogItem::CALCULATION_PERCENTAGE) {
                            $set('amount', null);
                        } else {
                            $set('percentage', null);
                        }
                    })
                    ->columnSpan(2),
                TextInput::make('amount')
                    ->label(__('padelnis::filament/resources/special-price.fields.amount'))
                    ->inputMode('numeric')
                    ->prefix('Rp')
                    ->required(fn (Get $get): bool => blank($get('component')) || $get('calculation_type') !== CatalogItem::CALCULATION_PERCENTAGE)
                    ->visible(fn (Get $get): bool => blank($get('component')) || $get('calculation_type') !== CatalogItem::CALCULATION_PERCENTAGE)
                    ->rule('numeric')
                    ->rule('min:0')
                    ->formatStateUsing(fn (mixed $state): ?string => Reservation::formatTransferAmountForForm($state))
                    ->mutateStateForValidationUsing(fn (mixed $state): ?string => Reservation::normalizeTransferAmount($state))
                    ->dehydrateStateUsing(fn (mixed $state, Get $get): ?string => blank($get('component')) || $get('calculation_type') !== CatalogItem::CALCULATION_PERCENTAGE
                        ? Reservation::normalizeTransferAmount($state)
                        : '0.00')
                    ->columnSpan(2),
                TextInput::make('percentage')
                    ->label(__('padelnis::filament/resources/special-price.fields.percentage'))
                    ->numeric()
                    ->suffix('%')
                    ->minValue(0)
                    ->maxValue(100)
                    ->required(fn (Get $get): bool => filled($get('component')) && $get('calculation_type') === CatalogItem::CALCULATION_PERCENTAGE)
                    ->visible(fn (Get $get): bool => filled($get('component')) && $get('calculation_type') === CatalogItem::CALCULATION_PERCENTAGE)
                    ->columnSpan(2),
                Select::make('basis')
                    ->label(__('padelnis::filament/resources/special-price.fields.basis'))
                    ->options(fn (): array => CatalogItem::priceComponentBasisOptions())
                    ->default(CatalogItem::BASIS_QUOTED_AMOUNT)
                    ->required(fn (Get $get): bool => filled($get('component')) && $get('calculation_type') === CatalogItem::CALCULATION_PERCENTAGE)
                    ->visible(fn (Get $get): bool => filled($get('component')) && $get('calculation_type') === CatalogItem::CALCULATION_PERCENTAGE)
                    ->columnSpan(2),
                Select::make('court_type')
                    ->label(__('padelnis::filament/resources/special-price.fields.court_type'))
                    ->options(fn (): array => Court::courtTypeOptions())
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('court_id', null);
                    })
                    ->visible(fn (Get $get): bool => static::catalogItemRequiresCourt($get('catalog_item_id')))
                    ->dehydrated(fn (Get $get): bool => static::catalogItemRequiresCourt($get('catalog_item_id')))
                    ->placeholder(__('padelnis::filament/resources/special-price.placeholders.all_court_types'))
                    ->columnSpan(2),
                Select::make('court_id')
                    ->label(__('padelnis::filament/resources/special-price.fields.court'))
                    ->options(fn (Get $get): array => Court::idOptions($get('court_type')))
                    ->searchable()
                    ->visible(fn (Get $get): bool => static::catalogItemRequiresCourt($get('catalog_item_id')))
                    ->dehydrated(fn (Get $get): bool => static::catalogItemRequiresCourt($get('catalog_item_id')))
                    ->placeholder(__('padelnis::filament/resources/special-price.placeholders.all_courts'))
                    ->columnSpan(2),
                Select::make('coach_id')
                    ->label(__('padelnis::filament/resources/special-price.fields.coach'))
                    ->options(fn (): array => Coach::options())
                    ->searchable()
                    ->visible(fn (Get $get): bool => static::catalogItemRequiresCoach($get('catalog_item_id')))
                    ->dehydrated(fn (Get $get): bool => static::catalogItemRequiresCoach($get('catalog_item_id')))
                    ->placeholder(__('padelnis::filament/resources/special-price.placeholders.all_coaches'))
                    ->columnSpan(2),
                Select::make('time_slot')
                    ->label(__('padelnis::filament/resources/special-price.fields.time_slot'))
                    ->options(fn (): array => static::slotOptions())
                    ->placeholder(__('padelnis::filament/resources/special-price.placeholders.all_slots'))
                    ->searchable()
                    ->visible(fn (Get $get): bool => static::catalogItemUsesTimedResources($get('catalog_item_id')))
                    ->dehydrated(fn (Get $get): bool => static::catalogItemUsesTimedResources($get('catalog_item_id')))
                    ->columnSpan(2),
                Select::make('day_type')
                    ->label(__('padelnis::filament/resources/special-price.fields.day_type'))
                    ->options(fn (): array => SpecialPrice::dayTypeOptions())
                    ->placeholder(__('padelnis::filament/resources/special-price.placeholders.all_days'))
                    ->columnSpan(2),
                DatePicker::make('starts_at')
                    ->label(__('padelnis::filament/resources/special-price.fields.starts_at'))
                    ->native(false)
                    ->displayFormat('Y-m-d')
                    ->columnSpan(2),
                DatePicker::make('ends_at')
                    ->label(__('padelnis::filament/resources/special-price.fields.ends_at'))
                    ->native(false)
                    ->displayFormat('Y-m-d')
                    ->rule('nullable')
                    ->rule('after_or_equal:starts_at')
                    ->columnSpan(2),
                Toggle::make('is_active')
                    ->label(__('padelnis::filament/resources/special-price.fields.is_active'))
                    ->default(true)
                    ->columnSpan(2),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('catalogItem.name')
                    ->label(__('padelnis::filament/resources/special-price.table.columns.catalog_item'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('component')
                    ->label(__('padelnis::filament/resources/special-price.table.columns.component'))
                    ->state(fn (SpecialPrice $record): string => $record->componentLabel())
                    ->badge()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('padelnis::filament/resources/special-price.table.columns.name'))
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('calculation_type')
                    ->label(__('padelnis::filament/resources/special-price.table.columns.calculation_type'))
                    ->state(fn (SpecialPrice $record): string => static::calculationTypeOptions($record->component)[$record->calculation_type] ?? $record->calculation_type)
                    ->badge()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label(__('padelnis::filament/resources/special-price.table.columns.amount'))
                    ->state(fn (SpecialPrice $record): ?string => $record->calculation_type === CatalogItem::CALCULATION_PERCENTAGE
                        ? null
                        : $record->amount)
                    ->money('IDR')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('percentage')
                    ->label(__('padelnis::filament/resources/special-price.table.columns.percentage'))
                    ->state(fn (SpecialPrice $record): ?string => filled($record->percentage)
                        ? rtrim(rtrim(number_format((float) $record->percentage, 4, '.', ''), '0'), '.').'%'
                        : null)
                    ->placeholder('-'),
                TextColumn::make('court.name')
                    ->label(__('padelnis::filament/resources/special-price.table.columns.court'))
                    ->placeholder(__('padelnis::filament/resources/special-price.placeholders.all_courts')),
                TextColumn::make('court_type')
                    ->label(__('padelnis::filament/resources/special-price.table.columns.court_type'))
                    ->placeholder(__('padelnis::filament/resources/special-price.placeholders.all_court_types'))
                    ->toggleable(),
                TextColumn::make('coach.name')
                    ->label(__('padelnis::filament/resources/special-price.table.columns.coach'))
                    ->placeholder(__('padelnis::filament/resources/special-price.placeholders.all_coaches')),
                TextColumn::make('time_slot')
                    ->label(__('padelnis::filament/resources/special-price.table.columns.time_slot'))
                    ->placeholder(__('padelnis::filament/resources/special-price.placeholders.all_slots')),
                TextColumn::make('day_type')
                    ->label(__('padelnis::filament/resources/special-price.table.columns.day_type'))
                    ->state(fn (SpecialPrice $record): ?string => filled($record->day_type)
                        ? __('padelnis::filament/resources/special-price.day_types.'.$record->day_type)
                        : null)
                    ->placeholder(__('padelnis::filament/resources/special-price.placeholders.all_days'))
                    ->badge(),
                TextColumn::make('starts_at')
                    ->label(__('padelnis::filament/resources/special-price.table.columns.starts_at'))
                    ->date('d M Y')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label(__('padelnis::filament/resources/special-price.table.columns.ends_at'))
                    ->date('d M Y')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('is_active')
                    ->label(__('padelnis::filament/resources/special-price.table.columns.is_active'))
                    ->state(fn (SpecialPrice $record): string => $record->is_active
                        ? __('padelnis::filament/resources/special-price.status.active')
                        : __('padelnis::filament/resources/special-price.status.inactive'))
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('catalog_item_id')
                    ->label(__('padelnis::filament/resources/special-price.filters.catalog_item'))
                    ->options(fn (): array => CatalogItem::options())
                    ->searchable(),
                SelectFilter::make('component')
                    ->label(__('padelnis::filament/resources/special-price.filters.component'))
                    ->options(fn (): array => CatalogItem::priceComponentOptions()),
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
            'index' => Pages\ManageSpecialPrices::route('/'),
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

    /**
     * @return array<string, string>
     */
    protected static function componentOptionsForCatalogItem(mixed $catalogItemId): array
    {
        $catalogItem = blank($catalogItemId) ? null : CatalogItem::query()->find($catalogItemId);

        if (! $catalogItem instanceof CatalogItem || ! $catalogItem->hasPriceComponents()) {
            return [];
        }

        $components = $catalogItem->priceComponents();

        if ($components !== []) {
            $componentCodes = array_column($components, 'component');

            return collect(CatalogItem::priceComponentOptions())
                ->only($componentCodes)
                ->all();
        }

        return [];
    }

    protected static function catalogItemRequiresCourt(mixed $catalogItemId): bool
    {
        $catalogItem = blank($catalogItemId) ? null : CatalogItem::query()->find($catalogItemId);

        return $catalogItem instanceof CatalogItem && $catalogItem->requiresCourt();
    }

    protected static function catalogItemRequiresCoach(mixed $catalogItemId): bool
    {
        $catalogItem = blank($catalogItemId) ? null : CatalogItem::query()->find($catalogItemId);

        return $catalogItem instanceof CatalogItem && $catalogItem->requiresCoach();
    }

    protected static function catalogItemUsesComponents(mixed $catalogItemId): bool
    {
        $catalogItem = blank($catalogItemId) ? null : CatalogItem::query()->find($catalogItemId);

        return $catalogItem instanceof CatalogItem && $catalogItem->hasPriceComponents();
    }

    protected static function catalogItemUsesTimedResources(mixed $catalogItemId): bool
    {
        $catalogItem = blank($catalogItemId) ? null : CatalogItem::query()->find($catalogItemId);

        return $catalogItem instanceof CatalogItem && $catalogItem->usesTimedResources();
    }

    /**
     * @return array<string, string>
     */
    protected static function calculationTypeOptions(?string $component = null): array
    {
        $options = [
            CatalogItem::CALCULATION_FIXED      => __('padelnis::filament/resources/special-price.calculation_types.fixed'),
            CatalogItem::CALCULATION_PERCENTAGE => __('padelnis::filament/resources/special-price.calculation_types.percentage'),
        ];

        if (blank($component)) {
            return collect($options)
                ->only(CatalogItem::CALCULATION_FIXED)
                ->all();
        }

        return $options;
    }
}
