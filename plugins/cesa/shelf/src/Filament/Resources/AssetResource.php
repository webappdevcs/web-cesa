<?php

namespace Cesa\Shelf\Filament\Resources;

use Cesa\Shelf\Enums\AssetCondition;
use Cesa\Shelf\Enums\NbhStatus;
use Cesa\Shelf\Filament\Resources\AssetResource\Pages;
use Cesa\Shelf\Filament\Resources\AssetResource\RelationManagers\AssetTransfersRelationManager;
use Cesa\Shelf\Models\Asset;
use Cesa\Shelf\Models\AssetAttribute;
use Cesa\Shelf\Models\AssetLocation;
use Cesa\Shelf\Models\Brand;
use Cesa\Shelf\Models\Category;
use Cesa\Shelf\Models\CustomAssetAttribute;
use Cesa\Shelf\Models\User;
use Cesa\Shelf\Support\ShelfAttachmentField;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Grid as ComponentsGrid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Section as ComponentsSection;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Webkul\Support\Models\Company;

class AssetResource extends ShelfResource
{
    protected static ?string $model = Asset::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?int $navigationSort = null;

    protected static function normalizeCategoryIds(mixed $categoryIds): array
    {
        $ids = is_array($categoryIds) ? $categoryIds : [$categoryIds];

        return collect($ids)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    protected static function getCachedCustomAttribute(int $id): ?CustomAssetAttribute
    {
        return Cache::remember("custom_attribute_{$id}", 300, function () use ($id) {
            return CustomAssetAttribute::withTrashed()->find($id);
        });
    }

    protected static function getCachedCustomAttributesByCategory(array $categoryIds): array
    {
        $categoryIds = self::normalizeCategoryIds($categoryIds);

        if ($categoryIds === []) {
            return [];
        }

        $cacheKey = 'custom_attributes_category_'.implode('_', $categoryIds);

        return Cache::remember($cacheKey, 300, function () use ($categoryIds) {
            return CustomAssetAttribute::where('is_active', true)
                ->where(function ($query) use ($categoryIds) {
                    foreach ($categoryIds as $id) {
                        $query->orWhereJsonContains('category_id', (int) $id);
                    }

                    $query->orWhereJsonLength('category_id', 0);
                })
                ->orderBy('name')
                ->get()
                ->all();
        });
    }

    public static function getCategoryOptions(): array
    {
        return Cache::remember('asset_category_options', 300, function () {
            $categories = Category::whereNull('parent_id')
                ->with('children:id,name,parent_id')
                ->get(['id', 'name']);

            $options = [];
            foreach ($categories as $category) {
                if ($category->children->isNotEmpty()) {
                    $options[$category->name] = $category->children->pluck('name', 'id')->toArray();
                }
            }

            return $options;
        });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                ComponentsGrid::make(['default' => 1, 'sm' => 3])
                    ->schema([
                        // Kolom Kiri
                        Group::make([
                            Section::make('Informasi Utama')
                                ->description('Detail dasar mengenai aset.')
                                ->schema([
                                    // Input untuk nama
                                    TextInput::make('name')
                                        ->label(__('shelf::filament.resources.asset.fields.name'))
                                        ->required()
                                        ->maxLength(255)
                                        ->prefixIcon('heroicon-m-tag')
                                        ->columnSpanFull(),

                                    // Dropdown untuk memilih kategori
                                    Select::make('category_id')
                                        ->label(__('shelf::filament.resources.asset.fields.category'))
                                        ->options(self::getCategoryOptions())
                                        ->searchable()
                                        ->required()
                                        ->prefixIcon('heroicon-m-rectangle-stack')
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, callable $set) {
                                            $categoryIds = self::normalizeCategoryIds($state);

                                            $set('attributes', collect(self::getCachedCustomAttributesByCategory($categoryIds))
                                                ->map(fn (CustomAssetAttribute $attribute): array => [
                                                    'custom_attribute_id' => $attribute->id,
                                                    'attribute_value'     => '',
                                                ])
                                                ->all());
                                        }),

                                    Select::make('brand_id')
                                        ->translateLabel()
                                        ->options(fn () => Cache::remember('brand_options', 300, fn () => Brand::orderBy('name')->pluck('name', 'id')))
                                        ->searchable()
                                        ->required()
                                        ->prefixIcon('heroicon-m-star')
                                        ->createOptionForm([
                                            TextInput::make('name')
                                                ->required()
                                                ->prefixIcon('heroicon-m-star'),
                                        ])
                                        ->createOptionUsing(function ($data) {
                                            $brand = Brand::create([
                                                'name' => $data['name'],
                                            ]);
                                            Cache::forget('brand_options');

                                            return $brand->id;
                                        }),
                                ])
                                ->columns(2),

                            Repeater::make('attributes')
                                ->relationship('attributes')
                                ->schema([
                                    // Dropdown untuk memilih atribut
                                    Select::make('custom_attribute_id')
                                        ->label(__('shelf::filament.resources.asset.fields.attribute'))
                                        ->options(function (callable $get) {
                                            $categoryIds = self::normalizeCategoryIds($get('../../category_id'));
                                            $selectedId = $get('../custom_attribute_id');

                                            if ($categoryIds === []) {
                                                return [];
                                            }

                                            $selectedAttributes = collect($get('../../attributes') ?? [])
                                                ->pluck('custom_attribute_id')
                                                ->filter()
                                                ->map(fn (mixed $id): int => (int) $id)
                                                ->all();

                                            return collect(self::getCachedCustomAttributesByCategory($categoryIds))
                                                ->filter(function (CustomAssetAttribute $attribute) use ($selectedAttributes, $selectedId): bool {
                                                    return (int) $selectedId === $attribute->id
                                                        || ! in_array($attribute->id, $selectedAttributes, true);
                                                })
                                                ->pluck('name', 'id')
                                                ->all();
                                        })
                                        ->reactive()
                                        ->searchable()
                                        ->required()
                                        ->afterStateHydrated(function ($state, callable $set) {
                                            if ($state) {
                                                $customAttribute = self::getCachedCustomAttribute((int) $state);

                                                if ($customAttribute) {
                                                    $set('custom_attribute_id', $customAttribute->id);
                                                    $set('custom_attribute_label', $customAttribute->name);
                                                }
                                            }
                                        }),

                                    // Input untuk nilai atribut
                                    TextInput::make('attribute_value')
                                        ->label(__('shelf::filament.resources.asset.fields.attribute_value'))
                                        ->reactive()
                                        ->visible(fn (callable $get) => filled($get('custom_attribute_id'))
                                            && self::getCachedCustomAttribute((int) $get('custom_attribute_id'))?->type === 'text')
                                        ->afterStateHydrated(function ($state, callable $set) {
                                            $set('attribute_value', $state ?? '');
                                        }),

                                    TextInput::make('attribute_value')
                                        ->label(__('shelf::filament.resources.asset.fields.attribute_value'))
                                        ->required(fn (callable $get) => filled($get('custom_attribute_id'))
                                            && (self::getCachedCustomAttribute((int) $get('custom_attribute_id'))?->required ?? false))
                                        ->numeric()
                                        ->reactive()
                                        ->visible(fn (callable $get) => filled($get('custom_attribute_id'))
                                            && self::getCachedCustomAttribute((int) $get('custom_attribute_id'))?->type === 'number')
                                        ->afterStateHydrated(function ($state, callable $set) {
                                            $set('attribute_value', $state ?? '');
                                        }),

                                    Textarea::make('attribute_value')
                                        ->label(__('shelf::filament.resources.asset.fields.attribute_value'))
                                        ->required(fn (callable $get) => filled($get('custom_attribute_id'))
                                            && (self::getCachedCustomAttribute((int) $get('custom_attribute_id'))?->required ?? false))
                                        ->reactive()
                                        ->visible(fn (callable $get) => filled($get('custom_attribute_id'))
                                            && self::getCachedCustomAttribute((int) $get('custom_attribute_id'))?->type === 'textarea')
                                        ->afterStateHydrated(function ($state, callable $set) {
                                            $set('attribute_value', $state ?? '');
                                        }),

                                    DatePicker::make('attribute_value')
                                        ->label(__('shelf::filament.resources.asset.fields.attribute_value'))
                                        ->required(fn (callable $get) => filled($get('custom_attribute_id'))
                                            && (self::getCachedCustomAttribute((int) $get('custom_attribute_id'))?->required ?? false))
                                        ->reactive()
                                        ->visible(fn (callable $get) => filled($get('custom_attribute_id'))
                                            && self::getCachedCustomAttribute((int) $get('custom_attribute_id'))?->type === 'date')
                                        ->afterStateHydrated(function ($state, callable $set) {
                                            $set('attribute_value', $state ?? '');
                                        }),
                                ])
                                ->columns(2)
                                ->columnSpan(2)
                                ->visible(fn (callable $get) => $get('category_id') !== null)
                                ->afterStateHydrated(function ($state, callable $set, $record) {
                                    if ($record && $record->attributes) {
                                        $state = [];
                                        foreach ($record->attributes as $attribute) {
                                            $customAttribute = self::getCachedCustomAttribute((int) $attribute->custom_attribute_id);
                                            $state[] = [
                                                'custom_attribute_id'    => $attribute->custom_attribute_id,
                                                'custom_attribute_label' => $customAttribute ? $customAttribute->name : null,
                                                'attribute_value'        => $attribute->attribute_value,
                                            ];
                                        }
                                        $set('attributes', $state);
                                    }
                                }),

                            Section::make('Siklus Aset Tanda Terima')
                                ->schema([
                                    Select::make('condition_status')
                                        ->label(__('shelf::filament.resources.asset.lifecycle.condition_status'))
                                        ->options(AssetCondition::options())
                                        ->default(AssetCondition::Available->value)
                                        ->required()
                                        ->reactive()
                                        ->columnSpan(1)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            if (in_array($state, [AssetCondition::Lost->value, AssetCondition::Damaged->value], true)) {
                                                if (! $get('nbh_status') || $get('nbh_status') === NbhStatus::None->value) {
                                                    $set('nbh_status', NbhStatus::Pending->value);
                                                }
                                            } else {
                                                $set('nbh_status', NbhStatus::None->value);
                                                $set('nbh_responsible_user_id', null);
                                                $set('nbh_reported_at', null);
                                            }
                                        }),
                                    Select::make('nbh_status')
                                        ->label(__('shelf::filament.resources.asset.lifecycle.nbh_status'))
                                        ->options(function (callable $get): array {
                                            $condition = $get('condition_status');

                                            if (in_array($condition, [AssetCondition::Lost->value, AssetCondition::Damaged->value], true)) {
                                                return collect(NbhStatus::cases())
                                                    ->reject(fn (NbhStatus $status) => $status === NbhStatus::None)
                                                    ->mapWithKeys(fn (NbhStatus $status) => [$status->value => $status->label()])
                                                    ->toArray();
                                            }

                                            return NbhStatus::options();
                                        })
                                        ->reactive()
                                        ->columnSpan(1)
                                        ->visible(fn (callable $get) => in_array($get('condition_status'), [AssetCondition::Lost->value, AssetCondition::Damaged->value], true) || $get('nbh_status') !== NbhStatus::None->value),
                                    DatePicker::make('nbh_reported_at')
                                        ->label(__('shelf::filament.resources.asset.lifecycle.incident_date'))
                                        ->columnSpan(1)
                                        ->visible(fn (callable $get) => in_array($get('condition_status'), [AssetCondition::Lost->value, AssetCondition::Damaged->value], true) || $get('nbh_status') !== NbhStatus::None->value),
                                    Select::make('nbh_responsible_user_id')
                                        ->label(__('shelf::filament.resources.asset.lifecycle.responsible_person'))
                                        ->options(fn () => Cache::remember('shelf_selectable_user_options', 300, fn () => User::selectableOptions()))
                                        ->searchable()
                                        ->prefixIcon('heroicon-m-user')
                                        ->columnSpan(1)
                                        ->required(fn (callable $get) => $get('nbh_status') === NbhStatus::Resolved->value)
                                        ->visible(fn (callable $get) => in_array($get('condition_status'), [AssetCondition::Lost->value, AssetCondition::Damaged->value], true) || $get('nbh_status') === NbhStatus::Resolved->value),
                                    ShelfAttachmentField::make(
                                        'audit_document_path',
                                        'shelf/assets/audit-documents',
                                        ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
                                        4096,
                                    )
                                        ->label(__('shelf::filament.resources.asset.lifecycle.audit_document'))
                                        ->columnSpan(2)
                                        ->required(fn (callable $get) => $get('nbh_status') === NbhStatus::Resolved->value)
                                        ->visible(fn (callable $get) => in_array($get('condition_status'), [AssetCondition::Lost->value, AssetCondition::Damaged->value], true)),
                                    ShelfAttachmentField::make(
                                        'nbh_document_path',
                                        'shelf/assets/nbh-documents',
                                        ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
                                        4096,
                                    )
                                        ->label(__('shelf::filament.resources.asset.lifecycle.nbh_document'))
                                        ->columnSpan(2)
                                        ->required(fn (callable $get) => $get('nbh_status') === NbhStatus::Resolved->value)
                                        ->visible(fn (callable $get) => $get('nbh_status') === NbhStatus::Resolved->value),
                                    Textarea::make('nbh_notes')
                                        ->label(__('shelf::filament.resources.asset.lifecycle.nbh_notes'))
                                        ->placeholder(__('shelf::filament.resources.asset.lifecycle.nbh_notes_placeholder'))
                                        ->rows(3)
                                        ->columnSpanFull()
                                        ->visible(fn (callable $get) => in_array($get('condition_status'), [AssetCondition::Lost->value, AssetCondition::Damaged->value], true) || $get('nbh_status') !== NbhStatus::None->value),
                                ])
                                ->columns(2)
                                ->collapsed()
                                ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord && (auth()->user()?->can('update_shelf_asset') ?? false)),

                        ])->columnSpan(['sm' => 3, 'md' => 3, 'lg' => 2]),

                        // Kolom Kanan
                        Group::make([
                            Section::make('Detail Pembelian & Logistik')
                                ->schema([
                                    DatePicker::make('purchase_date')
                                        ->translateLabel()
                                        ->prefixIcon('heroicon-m-calendar-days')
                                        ->required(),
                                    Select::make('company_id')
                                        ->label('Badan Usaha')
                                        ->options(fn () => Cache::remember('asset_company_options', 300, fn () => Company::orderBy('name')->pluck('name', 'id')))
                                        ->searchable()
                                        ->prefixIcon('heroicon-m-building-office-2')
                                        ->required(),
                                    TextInput::make('item_price')
                                        ->translateLabel()
                                        ->prefix('Rp')
                                        ->numeric(),
                                    TextInput::make('qty')
                                        ->translateLabel()
                                        ->default(1)
                                        ->required()
                                        ->numeric(),
                                    Select::make('asset_location_id')
                                        ->translateLabel()
                                        ->options(fn () => Cache::remember('asset_location_options', 300, fn () => AssetLocation::orderBy('name')->pluck('name', 'id')))
                                        ->searchable()
                                        ->prefixIcon('heroicon-m-map-pin')
                                        ->createOptionForm([
                                            TextInput::make('name')
                                                ->translateLabel()
                                                ->required()
                                                ->maxLength(255),
                                            TextInput::make('address')
                                                ->translateLabel()
                                                ->maxLength(255),
                                            TextInput::make('description')
                                                ->translateLabel()
                                                ->maxLength(255),
                                        ])
                                        ->createOptionUsing(function ($data) {
                                            $assetLocation = AssetLocation::create([
                                                'name'        => $data['name'],
                                                'address'     => $data['address'],
                                                'description' => $data['description'],
                                            ]);
                                            Cache::forget('asset_location_options');

                                            return $assetLocation->id;
                                        }),
                                    ShelfAttachmentField::make(
                                        'image',
                                        'shelf/assets/images',
                                        ['image/jpeg', 'image/png', 'image/jpg', 'image/webp', 'image/gif'],
                                        2048,
                                        false,
                                        true,
                                    )
                                        ->label(__('shelf::filament.resources.asset.labels.asset_image'))
                                        ->maxSize(2048),
                                ])
                                ->columns(1),

                            Section::make('Informasi Penerima')
                                ->schema([
                                    Select::make('recipient_company_id')
                                        ->label('Badan Usaha Penerima')
                                        ->options(fn () => Cache::remember('recipient_company_options', 300, fn () => Company::orderBy('name')->pluck('name', 'id')))
                                        ->searchable()
                                        ->prefixIcon('heroicon-m-building-office-2'),
                                    Select::make('recipient_id')
                                        ->translateLabel()
                                        ->options(fn () => Cache::remember('shelf_selectable_user_options', 300, fn () => User::selectableOptions()))
                                        ->searchable()
                                        ->prefixIcon('heroicon-m-user'),
                                ])
                                ->columns(1)
                                ->collapsed()
                                ->visible(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\EditRecord && (auth()->user()?->can('update_shelf_asset') ?? false)),
                        ])->columnSpan(['sm' => 3, 'md' => 3, 'lg' => 1]),
                    ]),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'company',
                'category',
                'brand',
                'assetLocation',
                'recipient',
                'latestTransferDetail.assetTransfer',
            ]))
            ->columns([
                TextColumn::make('purchase_date')->translateLabel()->date()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('company.name')
                    ->label('Badan Usaha')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('name')->translateLabel()->sortable()->searchable()->toggleable(),
                TextColumn::make('category.name')->translateLabel()->sortable()->toggleable(),
                TextColumn::make('brand.name')->translateLabel()->sortable()->searchable()->toggleable(),
                TextColumn::make('type')->translateLabel()->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('serial_number')->translateLabel()->sortable()->searchable()->toggleable(),
                TextColumn::make('imei1')->translateLabel()->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('imei2')->translateLabel()->sortable()->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('item_price')->translateLabel()->sortable()->money('IDR', true)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('item_age')
                    ->translateLabel()
                    ->sortable(query: fn ($query, $direction) => $query->sortByItemAge($direction))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('qty')
                    ->translateLabel()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state <= 0  => 'danger',
                        $state === 1 => 'warning',
                        default      => 'success',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('assetLocation.name')->translateLabel()->sortable()->searchable()->toggleable(),
                TextColumn::make('condition_status')
                    ->label(__('shelf::filament.resources.asset.lifecycle.condition_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? 'Tidak Diketahui')
                    ->color(fn ($state) => $state?->color() ?? 'secondary')
                    ->toggleable(),
                TextColumn::make('nbh_status')
                    ->label(__('shelf::filament.resources.asset.lifecycle.nbh_status'))
                    ->badge()
                    ->formatStateUsing(fn (?NbhStatus $state): string => $state?->label() ?? NbhStatus::None->label())
                    ->color(fn (?NbhStatus $state): string => $state?->color() ?? NbhStatus::None->color())
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('company')
                    ->relationship('company', 'name')
                    ->label('Badan Usaha')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label(__('shelf::filament.resources.asset.labels.category'))
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('condition_status')
                    ->label(__('shelf::filament.resources.asset.lifecycle.condition_status'))
                    ->options(AssetCondition::options())
                    ->multiple(),
                SelectFilter::make('nbh_status')
                    ->label(__('shelf::filament.resources.asset.lifecycle.nbh_status'))
                    ->options(NbhStatus::options())
                    ->multiple(),
                SelectFilter::make('assetLocation')
                    ->relationship('assetLocation', 'name')
                    ->translateLabel()
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Filter::make('table_data_filter')
                    ->label(__('shelf::filament.resources.asset.filters.label'))
                    ->form([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('serial_number')
                                    ->label(__('shelf::filament.resources.asset.filters.serial_number'))
                                    ->placeholder(__('shelf::filament.resources.asset.filters.serial_number_placeholder')),
                                TextInput::make('imei')
                                    ->label(__('shelf::filament.resources.asset.filters.imei'))
                                    ->placeholder(__('shelf::filament.resources.asset.filters.imei_placeholder')),
                                TextInput::make('item_price_min')
                                    ->label(__('shelf::filament.resources.asset.filters.min_price'))
                                    ->numeric(),
                                TextInput::make('item_price_max')
                                    ->label(__('shelf::filament.resources.asset.filters.max_price'))
                                    ->numeric(),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(filled($data['serial_number'] ?? null), fn (Builder $q) => $q->where('serial_number', 'like', '%'.trim($data['serial_number']).'%'))
                            ->when(filled($data['imei'] ?? null), function (Builder $q) use ($data) {
                                $imei = trim($data['imei']);

                                $q->where(function (Builder $imeiQuery) use ($imei) {
                                    $imeiQuery
                                        ->where('imei1', 'like', '%'.$imei.'%')
                                        ->orWhere('imei2', 'like', '%'.$imei.'%');
                                });
                            })
                            ->when(filled($data['item_price_min'] ?? null), fn (Builder $q) => $q->where('item_price', '>=', (float) $data['item_price_min']))
                            ->when(filled($data['item_price_max'] ?? null), fn (Builder $q) => $q->where('item_price', '<=', (float) $data['item_price_max']));
                    }),
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->columnToggleFormColumns(2)
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormWidth('4xl')
            ->filtersTriggerAction(fn (Action $action) => $action
                ->label(__('shelf::filament.resources.asset.filters.filter_audit'))
                ->slideOver())
            ->actions([
                ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
                BulkAction::make('pindahkanKeAtribut')
                    ->label(__('shelf::filament.resources.asset.actions.move_to_attributes'))
                    ->action(fn (Collection $records) => self::pindahkanKeAssetAttributeBulk($records))
                    ->requiresConfirmation()
                    ->color('primary')
                    ->icon('heroicon-o-arrow-right'), // Ikon untuk bulk action
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AssetTransfersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAssets::route('/'),
            'create' => Pages\CreateAsset::route('/create'),
            'edit'   => Pages\EditAsset::route('/{record}/edit'),
            'view'   => Pages\ViewAsset::route('/{record}'),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                ComponentsGrid::make(['default' => 1, 'sm' => 3])
                    ->schema([
                        Group::make([
                            ComponentsSection::make(__('shelf::filament.resources.asset.info_section'))
                                ->schema([
                                    ComponentsGrid::make(3)
                                        ->schema([
                                            Group::make([
                                                TextEntry::make('name')
                                                    ->label(__('shelf::filament.resources.asset.fields.name'))
                                                    ->extraAttributes(['class' => 'text-xl font-bold text-primary-600 dark:text-primary-400 mb-2'])
                                                    ->columnSpan(2),
                                                ComponentsGrid::make(2)
                                                    ->schema([
                                                        TextEntry::make('category.name')
                                                            ->label(__('shelf::filament.resources.asset.labels.category'))
                                                            ->icon('heroicon-m-tag'),
                                                        TextEntry::make('brand.name')
                                                            ->label(__('shelf::filament.resources.asset.labels.brand'))
                                                            ->icon('heroicon-m-building-storefront'),
                                                        TextEntry::make('type')
                                                            ->label(__('shelf::filament.resources.asset.labels.type'))
                                                            ->icon('heroicon-m-squares-plus'),
                                                    ]),
                                            ])->columnSpan(2),
                                            ImageEntry::make('image')
                                                ->label(__('shelf::filament.resources.asset.labels.asset_image'))
                                                ->hiddenLabel()
                                                ->getStateUsing(fn (Asset $record): ?string => $record->managedFileUrl('image'))
                                                ->checkFileExistence(false)
                                                ->width('100%')
                                                ->height(200)
                                                ->extraImgAttributes([
                                                    'class' => 'rounded-xl object-cover shadow-sm ring-1 ring-gray-200 dark:ring-gray-800',
                                                ])
                                                ->columnSpan(1),
                                        ]),
                                ]),

                            ComponentsSection::make(__('shelf::filament.resources.asset.attributes_section'))
                                ->schema([
                                    ComponentsGrid::make(3)
                                        ->schema(function ($record) {
                                            $record->loadMissing('attributes.customAttribute');

                                            return $record->attributes->map(function ($attribute) {
                                                return TextEntry::make("custom_attribute_{$attribute->custom_attribute_id}")
                                                    ->label($attribute->customAttribute?->name ?? 'Unknown Attribute')
                                                    ->state($attribute->attribute_value)
                                                    ->copyable();
                                            })->toArray();
                                        }),
                                ])
                                ->visible(fn (Asset $record): bool => $record->attributes()->count() > 0),

                            ComponentsSection::make(__('shelf::filament.resources.asset.documents_section'))
                                ->schema([
                                    ComponentsGrid::make(2)
                                        ->schema([
                                            TextEntry::make('audit_document_path')
                                                ->label(__('shelf::filament.resources.asset.lifecycle.audit_document'))
                                                ->url(fn (Asset $record): ?string => $record->managedFileUrl('audit_document_path'), true)
                                                ->openUrlInNewTab()
                                                ->icon('heroicon-m-document-text')
                                                ->visible(fn (Asset $record): bool => filled($record->audit_document_path)),
                                            TextEntry::make('nbh_document_path')
                                                ->label(__('shelf::filament.resources.asset.lifecycle.nbh_document'))
                                                ->url(fn (Asset $record): ?string => $record->managedFileUrl('nbh_document_path'), true)
                                                ->openUrlInNewTab()
                                                ->icon('heroicon-m-document-text')
                                                ->visible(fn (Asset $record): bool => filled($record->nbh_document_path)),
                                        ]),
                                ])
                                ->visible(fn (Asset $record): bool => filled($record->audit_document_path) || filled($record->nbh_document_path)),
                        ])->columnSpan(['sm' => 3, 'md' => 3, 'lg' => 2]),

                        Group::make([
                            ComponentsSection::make(__('shelf::filament.resources.asset.status_section'))
                                ->schema([
                                    TextEntry::make('condition_status_label')
                                        ->label(__('shelf::filament.resources.asset.lifecycle.condition_status'))
                                        ->badge()
                                        ->color(fn ($state, Asset $record): string => $record->condition_status_color ?? 'secondary'),
                                    TextEntry::make('nbh_status')
                                        ->label(__('shelf::filament.resources.asset.lifecycle.nbh_status'))
                                        ->badge()
                                        ->formatStateUsing(fn (?NbhStatus $state): string => $state?->label() ?? NbhStatus::None->label())
                                        ->color(fn (?NbhStatus $state): string => $state?->color() ?? NbhStatus::None->color()),
                                    TextEntry::make('validasi_status')
                                        ->label(__('shelf::filament.resources.asset.validation_status'))
                                        ->badge()
                                        ->color(fn ($state, Asset $record): string => $record->checkValidRecipient() ? 'success' : 'danger')
                                        ->state(fn (Asset $record): string => $record->checkValidRecipient() ? __('shelf::filament.resources.asset.valid') : __('shelf::filament.resources.asset.invalid')),

                                    ComponentsGrid::make(1)
                                        ->schema([
                                            TextEntry::make('asset_location_display')
                                                ->label(__('shelf::filament.resources.asset.labels.asset_location'))
                                                ->state(fn (Asset $record): string => $record->assetLocation?->name ?? '-')
                                                ->icon('heroicon-m-map-pin'),
                                            TextEntry::make('recipient_display')
                                                ->label(__('shelf::filament.resources.asset.asset_holder'))
                                                ->state(fn (Asset $record): string => $record->recipient?->name ?? '-')
                                                ->icon('heroicon-m-user'),
                                        ])->extraAttributes(['class' => 'mt-4']),

                                    ComponentsGrid::make(1)
                                        ->schema([
                                            TextEntry::make('nbh_reported_at_display')
                                                ->label(__('shelf::filament.resources.asset.lifecycle.incident_date'))
                                                ->state(fn (Asset $record): string => $record->nbh_status instanceof NbhStatus && $record->nbh_status !== NbhStatus::None
                                                    ? optional($record->nbh_reported_at)?->format('d M Y') ?? '-'
                                                    : '-')
                                                ->icon('heroicon-m-calendar'),
                                            TextEntry::make('nbh_responsible_display')
                                                ->label(__('shelf::filament.resources.asset.lifecycle.responsible_person'))
                                                ->state(fn (Asset $record): string => $record->nbh_status instanceof NbhStatus && $record->nbh_status !== NbhStatus::None
                                                    ? $record->nbhResponsible?->name ?? '-'
                                                    : '-')
                                                ->icon('heroicon-m-user-circle'),
                                        ])
                                        ->visible(fn (Asset $record): bool => $record->nbh_status instanceof NbhStatus && $record->nbh_status !== NbhStatus::None)
                                        ->extraAttributes(['class' => 'mt-4']),

                                    TextEntry::make('nbh_notes')
                                        ->label(__('shelf::filament.resources.asset.lifecycle.nbh_notes'))
                                        ->columnSpanFull()
                                        ->visible(fn (Asset $record): bool => filled($record->nbh_notes)),
                                ])->columns(2),

                            ComponentsSection::make(__('shelf::filament.resources.asset.purchase_section'))
                                ->schema([
                                    TextEntry::make('purchase_date')
                                        ->label(__('shelf::filament.resources.asset.labels.purchase_date'))
                                        ->formatStateUsing(fn ($state) => \Carbon\Carbon::parse($state)->format('d F Y'))
                                        ->icon('heroicon-m-calendar-days'),
                                    TextEntry::make('item_price')
                                        ->label(__('shelf::filament.resources.asset.labels.item_price'))
                                        ->formatStateUsing(fn ($state) => 'Rp '.number_format(intval($state), 0, ',', '.'))
                                        ->extraAttributes(['class' => 'text-lg font-bold text-green-600 dark:text-green-400'])
                                        ->icon('heroicon-m-banknotes'),
                                    TextEntry::make('qty')
                                        ->label(__('shelf::filament.resources.asset.labels.qty'))
                                        ->badge()
                                        ->color('info'),
                                    TextEntry::make('company.name')
                                        ->label(__('shelf::filament.resources.asset.labels.business_entity'))
                                        ->icon('heroicon-m-building-office-2'),
                                ])->columns(1),
                        ])->columnSpan(['sm' => 3, 'md' => 3, 'lg' => 1]),
                    ]),
            ])
            ->columns(1);
    }

    protected static function pindahkanKeAssetAttributeBulk($records)
    {
        foreach ($records as $record) {
            $attributes = [
                '3' => $record->serial_number,
                '1' => $record->imei1,
                '2' => $record->imei2,
            ];

            foreach ($attributes as $key => $value) {
                if (! is_null($value) && $value !== '') {
                    AssetAttribute::updateOrCreate(
                        [
                            'asset_id'            => $record->id,
                            'custom_attribute_id' => $key,
                        ],
                        [
                            'attribute_value' => $value,
                        ]
                    );
                }
            }
        }

        Notification::make()
            ->title(__('shelf::filament.resources.asset.notifications.success'))
            ->body(__('shelf::filament.resources.asset.notifications.attributes_moved'))
            ->success()
            ->send();
    }
}
