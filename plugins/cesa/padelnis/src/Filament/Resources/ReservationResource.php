<?php

namespace Cesa\Padelnis\Filament\Resources;

use Cesa\Padelnis\Filament\Resources\ReservationResource\Pages;
use Cesa\Padelnis\Models\CatalogItem;
use Cesa\Padelnis\Models\Coach;
use Cesa\Padelnis\Models\Court;
use Cesa\Padelnis\Models\Reservation;
use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Webkul\PluginManager\Package;
use Webkul\Security\Traits\HasResourcePermissionQuery;

class ReservationResource extends Resource
{
    use HasResourcePermissionQuery;

    protected static ?string $model = Reservation::class;

    protected static string|\BackedEnum|null $navigationIcon = null;

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return Package::isPluginInstalled('padelnis');
    }

    public static function getNavigationLabel(): string
    {
        return __('padelnis::filament/resources/reservation.navigation.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.padelnis');
    }

    public static function getModelLabel(): string
    {
        return __('padelnis::filament/resources/reservation.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('padelnis::filament/resources/reservation.plural');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['catalogItem', 'coach', 'reservationPriceLines']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('padelnis::filament/resources/reservation.form.sections.reservation.title'))
                    ->schema([
                        TextInput::make('id_reff')
                            ->label(__('padelnis::filament/resources/reservation.fields.id_reff'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (string $operation): bool => $operation !== 'create'),

                        Select::make('catalog_item_id')
                            ->label(__('padelnis::filament/resources/reservation.fields.catalog_item'))
                            ->options(fn (?Reservation $record = null): array => static::reservationCatalogItemOptions($record))
                            ->searchable()
                            ->live()
                            ->required(fn (?Reservation $record = null, string $operation = 'create'): bool => $operation === 'create' || filled($record?->catalog_item_id))
                            ->rule(fn (Get $get, ?Reservation $record = null, string $operation = 'create'): mixed => filled($get('catalog_item_id')) || $operation === 'create'
                                ? Rule::in(array_keys(static::reservationCatalogItemOptions($record)))
                                : null)
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                if (! Reservation::catalogItemRequiresCourt($get('catalog_item_id'))) {
                                    $set('court', null);
                                }

                                if (! Reservation::catalogItemRequiresCoach($get('catalog_item_id'))) {
                                    $set('coach_id', null);
                                }

                                if (! Reservation::catalogItemUsesTimedResources($get('catalog_item_id'))) {
                                    $set('reservation_time', null);
                                }

                                static::refreshExpectedAmount($set, $get);
                            })
                            ->placeholder(__('padelnis::filament/resources/reservation.form.placeholders.catalog_item')),
                        TextInput::make('customer_name')
                            ->label(__('padelnis::filament/resources/reservation.fields.customer_name'))
                            ->required()
                            ->maxLength(255)
                            ->placeholder(__('padelnis::filament/resources/reservation.form.placeholders.customer_name')),
                        DatePicker::make('reservation_date')
                            ->label(__('padelnis::filament/resources/reservation.fields.reservation_date'))
                            ->required()
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                static::refreshExpectedAmount($set, $get);
                            })
                            ->placeholder(__('padelnis::filament/resources/reservation.form.placeholders.reservation_date')),
                        Select::make('court')
                            ->label(__('padelnis::filament/resources/reservation.fields.court'))
                            ->options(fn (?Reservation $record = null): array => static::reservationCourtOptions($record))
                            ->searchable()
                            ->required(fn (Get $get): bool => Reservation::catalogItemRequiresCourt($get('catalog_item_id')))
                            ->visible(fn (Get $get): bool => filled($get('catalog_item_id')) && Reservation::catalogItemRequiresCourt($get('catalog_item_id')))
                            ->live()
                            ->rule(fn (Get $get, ?Reservation $record = null): mixed => Reservation::catalogItemRequiresCourt($get('catalog_item_id'))
                                ? Rule::in(array_keys(static::reservationCourtOptions($record)))
                                : null)
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                static::refreshExpectedAmount($set, $get);
                            })
                            ->placeholder(__('padelnis::filament/resources/reservation.form.placeholders.court')),
                        Select::make('coach_id')
                            ->label(__('padelnis::filament/resources/reservation.fields.coach'))
                            ->options(fn (?Reservation $record = null): array => static::reservationCoachOptions($record))
                            ->searchable()
                            ->live()
                            ->required(fn (Get $get): bool => Reservation::catalogItemRequiresCoach($get('catalog_item_id')))
                            ->visible(fn (Get $get): bool => filled($get('catalog_item_id')) && Reservation::catalogItemRequiresCoach($get('catalog_item_id')))
                            ->rule(fn (Get $get, ?Reservation $record = null): mixed => Reservation::catalogItemRequiresCoach($get('catalog_item_id'))
                                ? Rule::in(array_keys(static::reservationCoachOptions($record)))
                                : null)
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                static::refreshExpectedAmount($set, $get);
                            })
                            ->placeholder(__('padelnis::filament/resources/reservation.form.placeholders.coach')),
                        Select::make('reservation_time')
                            ->label(__('padelnis::filament/resources/reservation.fields.reservation_time'))
                            ->options(fn (): array => Reservation::reservableTimeOptions())
                            ->searchable()
                            ->required(fn (Get $get): bool => Reservation::catalogItemUsesTimedResources($get('catalog_item_id')))
                            ->visible(fn (Get $get): bool => filled($get('catalog_item_id')) && Reservation::catalogItemUsesTimedResources($get('catalog_item_id')))
                            ->live()
                            ->rule(fn (Get $get): mixed => Reservation::catalogItemUsesTimedResources($get('catalog_item_id'))
                                ? Rule::in(array_keys(Reservation::reservableTimeOptions()))
                                : null)
                            ->rule(static fn (Get $get, ?Reservation $record = null): Closure => static::activeSlotValidationRule($get, $record))
                            ->afterStateUpdated(function (Set $set, Get $get): void {
                                static::refreshExpectedAmount($set, $get);
                            })
                            ->placeholder(__('padelnis::filament/resources/reservation.form.placeholders.reservation_time')),
                        TextInput::make('expected_amount')
                            ->label(__('padelnis::filament/resources/reservation.fields.expected_amount'))
                            ->inputMode('numeric')
                            ->prefix('Rp')
                            ->readOnly()
                            ->dehydrated(false)
                            ->visible(fn (Get $get): bool => filled($get('catalog_item_id')))
                            ->formatStateUsing(fn (mixed $state): ?string => Reservation::formatTransferAmountForForm($state))
                            ->dehydrateStateUsing(fn (mixed $state): ?string => Reservation::normalizeTransferAmount($state))
                            ->placeholder(__('padelnis::filament/resources/reservation.form.placeholders.expected_amount')),
                        Textarea::make('price_breakdown_preview')
                            ->label(__('padelnis::filament/resources/reservation.fields.price_breakdown'))
                            ->disabled()
                            ->dehydrated(false)
                            ->rows(4)
                            ->visible(fn (Get $get): bool => filled($get('price_breakdown_preview')))
                            ->afterStateHydrated(function (Set $set, ?Reservation $record = null): void {
                                if (! $record instanceof Reservation) {
                                    return;
                                }

                                $set('price_breakdown_preview', implode(PHP_EOL, $record->priceBreakdownLabels()));
                            })
                            ->columnSpanFull(),
                        TextInput::make('transfer_amount')
                            ->label(__('padelnis::filament/resources/reservation.fields.transfer_amount'))
                            ->inputMode('numeric')
                            ->prefix('Rp')
                            ->required()
                            ->rule('numeric')
                            ->rule('min:0')
                            ->placeholder(__('padelnis::filament/resources/reservation.form.placeholders.transfer_amount'))
                            ->formatStateUsing(fn (mixed $state): ?string => Reservation::formatTransferAmountForForm($state))
                            ->mutateStateForValidationUsing(fn (mixed $state): ?string => Reservation::normalizeTransferAmount($state))
                            ->dehydrateStateUsing(fn (mixed $state): ?string => Reservation::normalizeTransferAmount($state))
                            ->extraAlpineAttributes([
                                'x-on:input' => static::transferAmountMaskAlpineExpression(),
                                'x-on:blur'  => static::transferAmountMaskAlpineExpression(),
                                'x-init'     => static::transferAmountMaskAlpineExpression(),
                            ]),
                        DatePicker::make('transfer_date')
                            ->label(__('padelnis::filament/resources/reservation.fields.transfer_date'))
                            ->native(false)
                            ->displayFormat('Y-m-d')
                            ->placeholder(__('padelnis::filament/resources/reservation.form.placeholders.transfer_date')),
                        Textarea::make('notes')
                            ->label(__('padelnis::filament/resources/reservation.fields.notes'))
                            ->nullable()
                            ->maxLength(1000)
                            ->rows(3)
                            ->placeholder(__('padelnis::filament/resources/reservation.form.placeholders.notes'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        Group::make()
                            ->columnSpan(2)
                            ->schema([
                                Section::make(__('padelnis::filament/resources/reservation.form.sections.reservation.title'))
                                    ->schema([
                                        TextEntry::make('customer_name')
                                            ->label(__('padelnis::filament/resources/reservation.fields.customer_name')),
                                        TextEntry::make('catalogItem.name')
                                            ->label(__('padelnis::filament/resources/reservation.fields.catalog_item'))
                                            ->placeholder('-'),
                                        TextEntry::make('coach.name')
                                            ->label(__('padelnis::filament/resources/reservation.fields.coach'))
                                            ->placeholder('-'),
                                        TextEntry::make('reservation_date')
                                            ->label(__('padelnis::filament/resources/reservation.fields.reservation_date'))
                                            ->date('d M Y'),
                                        TextEntry::make('court')
                                            ->label(__('padelnis::filament/resources/reservation.fields.court')),
                                        TextEntry::make('reservation_time')
                                            ->label(__('padelnis::filament/resources/reservation.fields.reservation_time')),
                                        TextEntry::make('blocked_slots')
                                            ->label(__('padelnis::filament/resources/reservation.fields.blocked_slots'))
                                            ->state(fn (Reservation $record): array => $record->blockedSlotLabels())
                                            ->badge()
                                            ->separator(', ')
                                            ->columnSpanFull(),
                                        TextEntry::make('notes')
                                            ->label(__('padelnis::filament/resources/reservation.fields.notes'))
                                            ->placeholder('-')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Section::make(__('padelnis::filament/resources/reservation.fields.price_breakdown'))
                                    ->schema([
                                        TextEntry::make('price_breakdown')
                                            ->hiddenLabel()
                                            ->state(fn (Reservation $record): array => $record->priceBreakdownLabels())
                                            ->listWithLineBreaks()
                                            ->placeholder('-'),
                                    ]),
                            ]),

                        Group::make()
                            ->columnSpan(1)
                            ->schema([
                                Section::make(__('padelnis::filament/resources/reservation.form.sections.payment.title'))
                                    ->schema([
                                        TextEntry::make('id_reff')
                                            ->label(__('padelnis::filament/resources/reservation.fields.id_reff'))
                                            ->copyable(),
                                        TextEntry::make('transaction_type')
                                            ->label(__('padelnis::filament/resources/reservation.fields.transaction_type'))
                                            ->state(fn (Reservation $record): string => $record->transactionTypeLabel())
                                            ->badge(),
                                        TextEntry::make('expected_amount')
                                            ->label(__('padelnis::filament/resources/reservation.fields.expected_amount'))
                                            ->money('IDR')
                                            ->placeholder('-'),
                                        TextEntry::make('transfer_amount')
                                            ->label(__('padelnis::filament/resources/reservation.fields.transfer_amount'))
                                            ->money('IDR'),
                                        TextEntry::make('transfer_date')
                                            ->label(__('padelnis::filament/resources/reservation.fields.transfer_date'))
                                            ->date('d M Y')
                                            ->placeholder('-'),
                                    ])
                                    ->columns(1),

                                Section::make(__('padelnis::filament/resources/reservation.form.sections.metadata.title'))
                                    ->schema([
                                        TextEntry::make('created_at')
                                            ->label(__('padelnis::filament/resources/reservation.fields.created_at'))
                                            ->dateTime('d M Y H:i'),
                                    ])
                                    ->columns(1),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id_reff')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.id_reff'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage(__('padelnis::filament/resources/reservation.actions.copy_id_reff'))
                    ->sortable(),

                TextColumn::make('customer_name')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.customer_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('transaction_type')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.transaction_type'))
                    ->state(fn (Reservation $record): string => $record->transactionTypeLabel())
                    ->badge()
                    ->sortable(),
                TextColumn::make('catalogItem.name')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.catalog_item'))
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('coach.name')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.coach'))
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('reservation_date')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.reservation_date'))
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('reservation_time')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.reservation_time'))
                    ->sortable(),
                TextColumn::make('blocked_slots')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.blocked_slots'))
                    ->state(fn (Reservation $record): string => $record->blockedSlotSummary())
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('court')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.court'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('transfer_amount')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.transfer_amount'))
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('expected_amount')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.expected_amount'))
                    ->money('IDR')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('price_breakdown')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.price_breakdown'))
                    ->state(fn (Reservation $record): string => implode(', ', $record->priceBreakdownLabels()))
                    ->placeholder('-')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('transfer_date')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.transfer_date'))
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.notes'))
                    ->limit(60)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('padelnis::filament/resources/reservation.table.columns.created_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([

                SelectFilter::make('transaction_type')
                    ->label(__('padelnis::filament/resources/reservation.filters.transaction_type'))
                    ->options(fn (): array => Reservation::transactionTypeOptions()),
                SelectFilter::make('catalog_item_id')
                    ->label(__('padelnis::filament/resources/reservation.filters.catalog_item'))
                    ->options(fn (): array => CatalogItem::options())
                    ->searchable(),
                SelectFilter::make('coach_id')
                    ->label(__('padelnis::filament/resources/reservation.filters.coach'))
                    ->options(fn (): array => Reservation::coachOptions())
                    ->searchable(),
                Filter::make('reservation_date')
                    ->form([
                        DatePicker::make('reservation_from')
                            ->label(__('padelnis::filament/resources/reservation.filters.reservation_from')),
                        DatePicker::make('reservation_until')
                            ->label(__('padelnis::filament/resources/reservation.filters.reservation_until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['reservation_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('reservation_date', '>=', $date))
                            ->when($data['reservation_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('reservation_date', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $from = $data['reservation_from'] ?? null;
                        $until = $data['reservation_until'] ?? null;

                        if (! $from && ! $until) {
                            return null;
                        }

                        $fromLabel = $from ? Carbon::parse($from)->format('d M Y') : null;
                        $untilLabel = $until ? Carbon::parse($until)->format('d M Y') : null;

                        if ($fromLabel && $untilLabel) {
                            return __('padelnis::filament/resources/reservation.filters.reservation_range', [
                                'from'  => $fromLabel,
                                'until' => $untilLabel,
                            ]);
                        }

                        return $fromLabel
                            ? __('padelnis::filament/resources/reservation.filters.reservation_from_value', ['date' => $fromLabel])
                            : __('padelnis::filament/resources/reservation.filters.reservation_until_value', ['date' => $untilLabel]);
                    }),
                SelectFilter::make('court')
                    ->label(__('padelnis::filament/resources/reservation.filters.court'))
                    ->options(fn (): array => Reservation::courtOptions())
                    ->searchable(),
                SelectFilter::make('reservation_time')
                    ->label(__('padelnis::filament/resources/reservation.filters.reservation_time'))
                    ->options(fn (): array => Reservation::reservableTimeOptions())
                    ->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
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
            'index'  => Pages\ListReservations::route('/'),
            'create' => Pages\CreateReservation::route('/create'),
            'view'   => Pages\ViewReservation::route('/{record}'),
            'edit'   => Pages\EditReservation::route('/{record}/edit'),
        ];
    }

    protected static function activeSlotValidationRule(Get $get, ?Reservation $record = null): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {

            if (Reservation::activeSlotExists(
                $get('court'),
                $get('reservation_date'),
                $value,
                $record?->getKey(),
                $get('catalog_item_id'),
                $get('coach_id'),
                $get('transaction_type') ?? $record?->transaction_type,
            )) {
                $fail(__('padelnis::filament/resources/reservation.validation.active_slot_unique'));
            }
        };
    }

    protected static function refreshExpectedAmount(Set $set, Get $get): void
    {
        $currentExpectedAmount = Reservation::normalizeTransferAmount($get('expected_amount'));
        $currentTransferAmount = Reservation::normalizeTransferAmount($get('transfer_amount'));
        $quote = Reservation::resolvePricingQuote(
            $get('catalog_item_id'),
            $get('reservation_date'),
            $get('court'),
            $get('coach_id'),
            $get('reservation_time'),
        );
        $expectedAmount = $quote['total'] ?? null;

        $formattedExpectedAmount = Reservation::formatTransferAmountForForm($expectedAmount);

        $set('expected_amount', $formattedExpectedAmount);
        $set('price_breakdown_preview', static::formatPricingQuotePreview($quote));

        if (filled($formattedExpectedAmount) && (blank($currentTransferAmount) || $currentTransferAmount === $currentExpectedAmount)) {
            $set('transfer_amount', $formattedExpectedAmount);
        }
    }

    /**
     * @return array<int, string>
     */
    protected static function reservationCatalogItemOptions(?Reservation $record = null): array
    {
        $options = CatalogItem::options();
        $catalogItemId = $record?->catalog_item_id;

        if (blank($catalogItemId) || array_key_exists((int) $catalogItemId, $options)) {
            return $options;
        }

        $catalogItem = CatalogItem::withTrashed()->find($catalogItemId);

        if ($catalogItem instanceof CatalogItem) {
            $options[(int) $catalogItem->getKey()] = $catalogItem->optionLabel();
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    protected static function reservationCourtOptions(?Reservation $record = null): array
    {
        $options = Reservation::courtOptions();
        $courtName = $record?->court;

        if (blank($courtName) && filled($record?->court_id)) {
            $courtName = Court::withTrashed()
                ->whereKey($record->court_id)
                ->value('name');
        }

        if (filled($courtName) && ! array_key_exists($courtName, $options)) {
            $options[$courtName] = $courtName;
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
    protected static function reservationCoachOptions(?Reservation $record = null): array
    {
        $options = Reservation::coachOptions();
        $coachId = $record?->coach_id;

        if (blank($coachId) || array_key_exists((int) $coachId, $options)) {
            return $options;
        }

        $coach = Coach::withTrashed()->find($coachId);

        if ($coach instanceof Coach) {
            $options[(int) $coach->getKey()] = $coach->name;
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>|null  $quote
     */
    protected static function formatPricingQuotePreview(?array $quote): ?string
    {
        if (! is_array($quote) || ! is_array($quote['lines'] ?? null)) {
            return null;
        }

        return collect($quote['lines'])
            ->filter(fn (mixed $line): bool => is_array($line))
            ->map(function (array $line): string {
                $component = $line['component'] ?? 'catalog_item';
                $componentLabel = CatalogItem::priceComponentOptions()[$component] ?? Str::headline(str_replace('_', ' ', $component));
                $amountLabel = 'Rp'.number_format((float) ($line['amount'] ?? 0), 0, ',', '.');

                if (($line['source'] ?? null) === 'missing_price_rule') {
                    return "{$componentLabel}: ".__('padelnis::filament/resources/reservation.price_breakdown.missing_rule');
                }

                return "{$componentLabel}: {$amountLabel}";
            })
            ->implode(PHP_EOL);
    }

    protected static function transferAmountMaskAlpineExpression(): string
    {
        return <<<'JS'
const value = String($el.value);
const integer = value.replace(/,\d{0,2}$/, '').replace(/\D/g, '');
$el.value = integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
JS;
    }
}
