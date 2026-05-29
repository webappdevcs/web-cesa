<?php

namespace Cesa\Padelnis\Models;

use Cesa\Padelnis\Database\Factories\CatalogItemFactory;
use Cesa\Padelnis\Enums\PricingMode;
use Cesa\Padelnis\Enums\TransactionType as TransactionTypeCode;
use Cesa\Padelnis\Services\PricingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Security\Traits\HasNullableCreator;

class CatalogItem extends Model
{
    use HasFactory, HasNullableCreator, SoftDeletes;

    public const string PRICE_COMPONENT_COURT = 'court';

    public const string PRICE_COMPONENT_COACH = 'coach';

    public const string CALCULATION_FIXED = 'fixed';

    public const string CALCULATION_PERCENTAGE = 'percentage';

    public const string CALCULATION_CATALOG_ITEM = 'catalog_item';

    public const string BASIS_QUOTED_AMOUNT = 'quoted_amount';

    public const string BASIS_TRANSFER_AMOUNT = 'transfer_amount';

    protected $table = 'padelnis_catalog_items';

    protected $fillable = [
        'name',
        'description',
        'transaction_type',
        'requires_court',
        'requires_coach',
        'pricing_mode',
        'time_slot',
        'price_amount',
        'price_components',
        'duration_hours',
        'session_count',
        'is_active',
        'allow_public_booking',
        'sort',
    ];

    protected static function booted(): void
    {
        static::saving(function (CatalogItem $catalogItem): void {
            $catalogItem->syncRequirementAttributes();
        });

        static::creating(function (CatalogItem $catalogItem): void {
            if ((int) ($catalogItem->sort ?? 0) <= 0) {
                $catalogItem->sort = static::nextSortValue();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_court'       => 'boolean',
            'requires_coach'       => 'boolean',
            'pricing_mode'         => PricingMode::class,
            'price_amount'         => 'decimal:2',
            'price_components'     => 'array',
            'duration_hours'       => 'integer',
            'session_count'        => 'integer',
            'is_active'            => 'boolean',
            'allow_public_booking' => 'boolean',
            'sort'                 => 'integer',
            'created_at'           => 'datetime',
            'updated_at'           => 'datetime',
            'deleted_at'           => 'datetime',
        ];
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function specialPrices(): HasMany
    {
        return $this->hasMany(SpecialPrice::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePubliclyBookable(Builder $query): Builder
    {
        return $query
            ->active()
            ->where('allow_public_booking', true);
    }

    /**
     * @return array<int, string>
     */
    public static function optionsForTransactionType(mixed $transactionType, ?string $timeSlot = null): array
    {
        return static::query()
            ->active()
            ->where('transaction_type', TransactionType::normalizeCode($transactionType))
            ->when($timeSlot !== null, fn ($query) => $query->where(function ($q) use ($timeSlot): void {
                $q->where('time_slot', $timeSlot)->orWhereNull('time_slot');
            }))
            ->orderBy('sort')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (self $item): array => [$item->getKey() => $item->optionLabel()])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function options(): array
    {
        return static::query()
            ->active()
            ->orderBy('transaction_type')
            ->orderBy('sort')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (self $item): array => [$item->getKey() => $item->optionLabel()])
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function publicOptions(): array
    {
        return static::query()
            ->publiclyBookable()
            ->orderBy('sort')
            ->orderBy('transaction_type')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (self $item): array => [$item->getKey() => $item->optionLabel()])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function priceComponentOptions(): array
    {
        return [
            self::PRICE_COMPONENT_COURT => __('padelnis::filament/resources/catalog-item.price_components.components.court'),
            self::PRICE_COMPONENT_COACH => __('padelnis::filament/resources/catalog-item.price_components.components.coach'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function priceComponentOptionsForRequirements(bool $requiresCourt, bool $requiresCoach): array
    {
        return collect(static::priceComponentOptions())
            ->when(! $requiresCourt, fn ($options) => $options->except(self::PRICE_COMPONENT_COURT))
            ->when(! $requiresCoach, fn ($options) => $options->except(self::PRICE_COMPONENT_COACH))
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function priceComponentCalculationOptions(): array
    {
        return [
            self::CALCULATION_FIXED        => __('padelnis::filament/resources/catalog-item.price_components.calculation_types.fixed'),
            self::CALCULATION_PERCENTAGE   => __('padelnis::filament/resources/catalog-item.price_components.calculation_types.percentage'),
            self::CALCULATION_CATALOG_ITEM => __('padelnis::filament/resources/catalog-item.price_components.calculation_types.catalog_item'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function priceComponentBasisOptions(): array
    {
        return [
            self::BASIS_QUOTED_AMOUNT   => __('padelnis::filament/resources/catalog-item.price_components.bases.quoted_amount'),
            self::BASIS_TRANSFER_AMOUNT => __('padelnis::filament/resources/catalog-item.price_components.bases.transfer_amount'),
        ];
    }

    public function optionLabel(): string
    {
        $label = sprintf('%s - %s', TransactionType::labelFor($this->transaction_type), $this->name);

        if (filled($this->time_slot)) {
            $label .= " ({$this->time_slot})";
        }

        return $label;
    }

    public function priceFor(mixed $reservationDate = null, ?int $courtId = null, ?int $coachId = null, ?string $timeSlot = null): string
    {
        return app(PricingService::class)
            ->quote($this, $reservationDate, $courtId, $coachId, $timeSlot)['total'];
    }

    public function hasPriceComponents(): bool
    {
        return ! blank($this->getRawOriginal('price_components') ?? $this->attributes['price_components'] ?? null);
    }

    /**
     * @return list<array{component: string, calculation_type: ?string, amount: ?string, percentage: ?float, basis: string, source_catalog_item_id: ?int, is_commissionable: bool}>
     */
    public function priceComponents(): array
    {
        return static::normalizePriceComponents(
            $this->price_components,
            $this->requiresCourt(),
            $this->requiresCoach(),
        );
    }

    public function priceComponentSummary(): ?string
    {
        $components = $this->priceComponents();

        if ($components === []) {
            return null;
        }

        return collect($components)
            ->map(function (array $component): string {
                $componentLabel = static::priceComponentOptions()[$component['component']] ?? $component['component'];

                $calculationLabel = match ($component['calculation_type']) {
                    self::CALCULATION_FIXED => __('padelnis::filament/resources/catalog-item.price_components.summary.fixed', [
                        'amount' => Reservation::formatTransferAmountForForm($component['amount']),
                    ]),
                    self::CALCULATION_PERCENTAGE => __('padelnis::filament/resources/catalog-item.price_components.summary.percentage', [
                        'percentage' => rtrim(rtrim(number_format((float) $component['percentage'], 4, '.', ''), '0'), '.'),
                    ]),
                    self::CALCULATION_CATALOG_ITEM => __('padelnis::filament/resources/catalog-item.price_components.summary.catalog_item'),
                    null                           => __('padelnis::filament/resources/catalog-item.price_components.summary.priced_by_rules'),
                    default                        => $component['calculation_type'],
                };

                return "{$componentLabel}: {$calculationLabel}";
            })
            ->implode(', ');
    }

    /**
     * @return list<array{component: string, calculation_type: ?string, amount: ?string, percentage: ?float, basis: string, source_catalog_item_id: ?int, is_commissionable: bool}>
     */
    public static function normalizePriceComponents(mixed $components, ?bool $requiresCourt = null, ?bool $requiresCoach = null): array
    {
        if (is_string($components)) {
            $decodedComponents = json_decode($components, true);
            $components = is_array($decodedComponents) ? $decodedComponents : [];
        }

        if (! is_array($components)) {
            $components = [];
        }

        $normalized = collect($components)
            ->filter(fn (mixed $component): bool => is_array($component))
            ->map(function (array $component) use ($requiresCourt, $requiresCoach): ?array {
                $componentType = $component['component'] ?? null;

                if (! in_array($componentType, [self::PRICE_COMPONENT_COURT, self::PRICE_COMPONENT_COACH], true)) {
                    return null;
                }

                if ($componentType === self::PRICE_COMPONENT_COURT && $requiresCourt === false) {
                    return null;
                }

                if ($componentType === self::PRICE_COMPONENT_COACH && $requiresCoach === false) {
                    return null;
                }

                $calculationType = $component['calculation_type'] ?? null;

                if ($calculationType !== null && ! in_array($calculationType, [self::CALCULATION_FIXED, self::CALCULATION_PERCENTAGE, self::CALCULATION_CATALOG_ITEM], true)) {
                    $calculationType = null;
                }

                $basis = $component['basis'] ?? self::BASIS_QUOTED_AMOUNT;

                if (! in_array($basis, [self::BASIS_QUOTED_AMOUNT, self::BASIS_TRANSFER_AMOUNT], true)) {
                    $basis = self::BASIS_QUOTED_AMOUNT;
                }

                $amount = Reservation::normalizeTransferAmount($component['amount'] ?? null);
                $percentage = blank($component['percentage'] ?? null) ? null : max(0, (float) $component['percentage']);

                return [
                    'component'              => $componentType,
                    'calculation_type'       => $calculationType,
                    'amount'                 => $calculationType === self::CALCULATION_FIXED ? $amount : null,
                    'percentage'             => $calculationType === self::CALCULATION_PERCENTAGE ? $percentage : null,
                    'basis'                  => $basis,
                    'source_catalog_item_id' => blank($component['source_catalog_item_id'] ?? null) ? null : (int) $component['source_catalog_item_id'],
                    'is_commissionable'      => $componentType === self::PRICE_COMPONENT_COACH && (bool) ($component['is_commissionable'] ?? false),
                ];
            })
            ->filter()
            ->values()
            ->all();

        $existingTypes = array_column($normalized, 'component');

        if ($requiresCourt === true && ! in_array(self::PRICE_COMPONENT_COURT, $existingTypes, true)) {
            $normalized[] = [
                'component'              => self::PRICE_COMPONENT_COURT,
                'calculation_type'       => null,
                'amount'                 => null,
                'percentage'             => null,
                'basis'                  => self::BASIS_QUOTED_AMOUNT,
                'source_catalog_item_id' => null,
                'is_commissionable'      => false,
            ];
        }

        if ($requiresCoach === true && ! in_array(self::PRICE_COMPONENT_COACH, $existingTypes, true)) {
            $normalized[] = [
                'component'              => self::PRICE_COMPONENT_COACH,
                'calculation_type'       => null,
                'amount'                 => null,
                'percentage'             => null,
                'basis'                  => self::BASIS_QUOTED_AMOUNT,
                'source_catalog_item_id' => null,
                'is_commissionable'      => false,
            ];
        }

        return collect($normalized)
            ->sortBy(fn (array $c): int => $c['component'] === self::PRICE_COMPONENT_COURT ? 0 : 1)
            ->values()
            ->all();
    }

    public function requiresCourt(): bool
    {
        return (bool) ($this->requires_court ?? true);
    }

    public function requiresCoach(): bool
    {
        if ($this->requires_coach !== null) {
            return (bool) $this->requires_coach;
        }

        return TransactionTypeCode::tryFrom(TransactionType::normalizeCode($this->transaction_type))?->requiresCoach() ?? false;
    }

    public function usesTimedResources(): bool
    {
        return $this->requiresCourt() || $this->requiresCoach();
    }

    public function setNameAttribute(mixed $value): void
    {
        $this->attributes['name'] = Reservation::normalizeDisplayName($value);
    }

    public function setDescriptionAttribute(mixed $value): void
    {
        $this->attributes['description'] = blank($value) ? null : trim((string) $value);
    }

    public function setTransactionTypeAttribute(mixed $value): void
    {
        $this->attributes['transaction_type'] = TransactionType::normalizeCode($value);
    }

    public function setPricingModeAttribute(mixed $value): void
    {
        $this->attributes['pricing_mode'] = PricingMode::fromValue($value)->value;
    }

    public function setPriceAmountAttribute(mixed $value): void
    {
        $this->attributes['price_amount'] = Reservation::normalizeTransferAmount($value);
    }

    public function setPriceComponentsAttribute(mixed $value): void
    {
        if (blank($value)) {
            $this->attributes['price_components'] = null;

            return;
        }

        $components = static::normalizePriceComponents(
            $value,
            $this->requiresCourt(),
            $this->requiresCoach(),
        );

        $this->attributes['price_components'] = $components === [] ? null : json_encode($components);
    }

    protected function syncRequirementAttributes(): void
    {
        if (! $this->usesTimedResources()) {
            $this->time_slot = null;
        }

        if (! $this->hasPriceComponents()) {
            return;
        }

        $components = static::normalizePriceComponents(
            $this->price_components,
            $this->requiresCourt(),
            $this->requiresCoach(),
        );

        $this->attributes['price_components'] = $components === [] ? null : json_encode($components);
    }

    public function setSortAttribute(mixed $value): void
    {
        $this->attributes['sort'] = blank($value) ? 0 : max(0, (int) $value);
    }

    protected static function newFactory(): CatalogItemFactory
    {
        return CatalogItemFactory::new();
    }

    protected static function nextSortValue(): int
    {
        return ((int) static::withTrashed()->max('sort')) + 1;
    }
}
