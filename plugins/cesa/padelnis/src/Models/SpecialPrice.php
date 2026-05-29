<?php

namespace Cesa\Padelnis\Models;

use Cesa\Padelnis\Database\Factories\SpecialPriceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Webkul\Security\Traits\HasNullableCreator;

class SpecialPrice extends Model
{
    use HasFactory, HasNullableCreator, SoftDeletes;

    protected $table = 'padelnis_special_prices';

    protected $fillable = [
        'catalog_item_id',
        'component',
        'court_id',
        'court_type',
        'coach_id',
        'time_slot',
        'day_type',
        'name',
        'amount',
        'calculation_type',
        'percentage',
        'basis',
        'priority',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saving(function (SpecialPrice $specialPrice): void {
            $specialPrice->syncCatalogItemRequirementAttributes();
            $specialPrice->normalizeComponentCalculationAttributes();
            $specialPrice->assertComponentMatchesCatalogItem();
            $specialPrice->assertCourtMatchesCourtType();
            $specialPrice->syncAutomaticPriority();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:2',
            'percentage' => 'decimal:4',
            'priority'   => 'integer',
            'starts_at'  => 'date',
            'ends_at'    => 'date',
            'is_active'  => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function currentFor(
        CatalogItem $catalogItem,
        mixed $reservationDate = null,
        ?int $courtId = null,
        ?int $coachId = null,
        ?string $timeSlot = null,
        ?string $component = null,
    ): ?self {
        $courtId = $catalogItem->requiresCourt() ? $courtId : null;
        $coachId = $catalogItem->requiresCoach() ? $coachId : null;
        $timeSlot = $catalogItem->usesTimedResources() ? $timeSlot : null;
        $date = static::normalizeDate($reservationDate) ?? now()->toDateString();
        $courtType = static::courtTypeFor($courtId);
        $dayType = static::dayTypeFor($date);

        return static::query()
            ->active()
            ->where('catalog_item_id', $catalogItem->getKey())
            ->where(function (Builder $query) use ($component): void {
                if ($component === null) {
                    $query->whereNull('component');

                    return;
                }

                $query->where('component', $component);
            })
            ->where(function (Builder $query) use ($date): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhereDate('starts_at', '<=', $date);
            })
            ->where(function (Builder $query) use ($date): void {
                $query
                    ->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', $date);
            })
            ->where(function (Builder $query) use ($courtId): void {
                $query->whereNull('court_id');

                if ($courtId !== null) {
                    $query->orWhere('court_id', $courtId);
                }
            })
            ->where(function (Builder $query) use ($courtType): void {
                $query->whereNull('court_type');

                if ($courtType !== null) {
                    $query->orWhere('court_type', $courtType);
                }
            })
            ->where(function (Builder $query) use ($coachId): void {
                $query->whereNull('coach_id');

                if ($coachId !== null) {
                    $query->orWhere('coach_id', $coachId);
                }
            })
            ->where(function (Builder $query) use ($timeSlot): void {
                $query->whereNull('time_slot');

                if ($timeSlot !== null) {
                    $query->orWhere('time_slot', $timeSlot);
                }
            })
            ->where(function (Builder $query) use ($dayType): void {
                $query->whereNull('day_type');

                if ($dayType !== null) {
                    $query->orWhere('day_type', $dayType);
                }
            })
            ->orderByDesc('priority')
            ->orderByRaw('CASE WHEN court_id IS NULL THEN 0 ELSE 1 END DESC')
            ->orderByRaw('CASE WHEN court_type IS NULL THEN 0 ELSE 1 END DESC')
            ->orderByRaw('CASE WHEN coach_id IS NULL THEN 0 ELSE 1 END DESC')
            ->orderByRaw('CASE WHEN time_slot IS NULL THEN 0 ELSE 1 END DESC')
            ->orderByRaw('CASE WHEN day_type IS NULL THEN 0 ELSE 1 END DESC')
            ->latest('starts_at')
            ->latest('id')
            ->first();
    }

    /**
     * @return array<string, string>
     */
    public static function dayTypeOptions(): array
    {
        return [
            'weekday' => __('padelnis::filament/resources/special-price.day_types.weekday'),
            'weekend' => __('padelnis::filament/resources/special-price.day_types.weekend'),
        ];
    }

    public function componentLabel(): string
    {
        if (blank($this->component)) {
            return __('padelnis::filament/resources/special-price.placeholders.total_price');
        }

        return CatalogItem::priceComponentOptions()[$this->component] ?? (string) $this->component;
    }

    public function setComponentAttribute(mixed $value): void
    {
        $this->attributes['component'] = in_array($value, [CatalogItem::PRICE_COMPONENT_COURT, CatalogItem::PRICE_COMPONENT_COACH], true)
            ? $value
            : null;
    }

    public function setCalculationTypeAttribute(mixed $value): void
    {
        $this->attributes['calculation_type'] = in_array($value, [CatalogItem::CALCULATION_FIXED, CatalogItem::CALCULATION_PERCENTAGE], true)
            ? $value
            : CatalogItem::CALCULATION_FIXED;
    }

    public function setPercentageAttribute(mixed $value): void
    {
        $this->attributes['percentage'] = blank($value) ? null : max(0, (float) $value);
    }

    public function setBasisAttribute(mixed $value): void
    {
        $this->attributes['basis'] = in_array($value, [CatalogItem::BASIS_QUOTED_AMOUNT, CatalogItem::BASIS_TRANSFER_AMOUNT], true)
            ? $value
            : CatalogItem::BASIS_QUOTED_AMOUNT;
    }

    public function setCourtTypeAttribute(mixed $value): void
    {
        $this->attributes['court_type'] = Court::normalizeCourtType($value);
    }

    public function setDayTypeAttribute(mixed $value): void
    {
        $this->attributes['day_type'] = in_array($value, ['weekday', 'weekend'], true) ? $value : null;
    }

    public function setNameAttribute(mixed $value): void
    {
        $this->attributes['name'] = blank($value) ? null : Reservation::normalizeDisplayName($value);
    }

    public function setAmountAttribute(mixed $value): void
    {
        $this->attributes['amount'] = Reservation::normalizeTransferAmount($value) ?? '0.00';
    }

    public function setPriorityAttribute(mixed $value): void
    {
        $this->attributes['priority'] = blank($value) ? 0 : max(0, (int) $value);
    }

    protected function normalizeComponentCalculationAttributes(): void
    {
        if (blank($this->component)) {
            if (($this->calculation_type ?? CatalogItem::CALCULATION_FIXED) === CatalogItem::CALCULATION_PERCENTAGE) {
                throw ValidationException::withMessages([
                    'calculation_type' => __('padelnis::filament/resources/special-price.validation.percentage_requires_component'),
                ]);
            }

            $this->component = null;
            $this->calculation_type = CatalogItem::CALCULATION_FIXED;
            $this->percentage = null;
            $this->basis = CatalogItem::BASIS_QUOTED_AMOUNT;

            return;
        }

        if (($this->calculation_type ?? CatalogItem::CALCULATION_FIXED) !== CatalogItem::CALCULATION_PERCENTAGE) {
            $this->calculation_type = CatalogItem::CALCULATION_FIXED;
            $this->percentage = null;
            $this->basis = CatalogItem::BASIS_QUOTED_AMOUNT;

            return;
        }

        $this->amount = '0.00';
        $this->basis = $this->basis ?: CatalogItem::BASIS_QUOTED_AMOUNT;
    }

    protected function syncCatalogItemRequirementAttributes(): void
    {
        $catalogItem = $this->catalogItem()->first();

        if (! $catalogItem instanceof CatalogItem) {
            return;
        }

        if (! $catalogItem->requiresCourt()) {
            $this->court_id = null;
            $this->court_type = null;
        }

        if (! $catalogItem->requiresCoach()) {
            $this->coach_id = null;
        }

        if (! $catalogItem->usesTimedResources()) {
            $this->time_slot = null;
        }
    }

    protected function assertComponentMatchesCatalogItem(): void
    {
        $catalogItem = $this->catalogItem()->first();

        if (! $catalogItem instanceof CatalogItem || blank($this->component)) {
            return;
        }

        $allowedComponents = $catalogItem->priceComponents() !== []
            ? array_column($catalogItem->priceComponents(), 'component')
            : array_keys(CatalogItem::priceComponentOptionsForRequirements(
                $catalogItem->requiresCourt(),
                $catalogItem->requiresCoach(),
            ));

        if (! in_array($this->component, $allowedComponents, true)) {
            throw ValidationException::withMessages([
                'component' => __('padelnis::filament/resources/special-price.validation.component_not_allowed'),
            ]);
        }

        if ($this->calculation_type === CatalogItem::CALCULATION_PERCENTAGE && blank($this->percentage)) {
            throw ValidationException::withMessages([
                'percentage' => __('padelnis::filament/resources/special-price.validation.percentage_required'),
            ]);
        }
    }

    protected function assertCourtMatchesCourtType(): void
    {
        if (blank($this->court_id) || blank($this->court_type)) {
            return;
        }

        $courtType = Court::withTrashed()
            ->whereKey($this->court_id)
            ->value('court_type');

        if ($courtType === $this->court_type) {
            return;
        }

        throw ValidationException::withMessages([
            'court_id' => __('padelnis::filament/resources/special-price.validation.court_type_mismatch'),
        ]);
    }

    protected function syncAutomaticPriority(): void
    {
        $priority = 0;

        if (filled($this->component)) {
            $priority += 128;
        }

        if (filled($this->court_id)) {
            $priority += 64;
        }

        if (filled($this->court_type)) {
            $priority += 32;
        }

        if (filled($this->coach_id)) {
            $priority += 16;
        }

        if (filled($this->time_slot)) {
            $priority += 8;
        }

        if (filled($this->day_type)) {
            $priority += 4;
        }

        if (filled($this->starts_at)) {
            $priority += 2;
        }

        if (filled($this->ends_at)) {
            $priority += 1;
        }

        $this->priority = $priority;
    }

    protected static function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function courtTypeFor(?int $courtId): ?string
    {
        if ($courtId === null) {
            return null;
        }

        return Court::query()
            ->whereKey($courtId)
            ->value('court_type');
    }

    protected static function dayTypeFor(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }

        try {
            return Carbon::parse($date)->isWeekend() ? 'weekend' : 'weekday';
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function newFactory(): SpecialPriceFactory
    {
        return SpecialPriceFactory::new();
    }
}
