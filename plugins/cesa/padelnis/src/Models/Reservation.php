<?php

namespace Cesa\Padelnis\Models;

use Cesa\Padelnis\Database\Factories\ReservationFactory;
use Cesa\Padelnis\Enums\TransactionType;
use Cesa\Padelnis\Models\TransactionType as TransactionTypeMaster;
use Cesa\Padelnis\Services\PricingService;
use Cesa\Padelnis\Services\ReservationReferenceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Webkul\Security\Traits\HasNullableCreator;

class Reservation extends Model
{
    use HasFactory, HasNullableCreator, SoftDeletes;

    protected const int REFERENCE_GENERATION_MAX_ATTEMPTS = 5;

    protected $table = 'padelnis_reservations';

    protected $fillable = [
        'id_reff',

        'transaction_type',
        'catalog_item_id',
        'customer_name',
        'reservation_date',
        'court_id',
        'court',
        'coach_id',
        'reservation_time',
        'expected_amount',
        'quoted_amount',
        'pricing_snapshot',
        'transfer_amount',
        'transfer_date',
        'notes',
    ];

    protected static function booted(): void
    {
        static::saving(function (Reservation $reservation): void {
            $reservation->syncTransactionDefaults();
            $reservation->syncCourtIdFromName();
            $reservation->syncExpectedAmount();
            $reservation->assertPricingQuoteIsConfigured();
            $reservation->assertTransactionRequirements();
            $reservation->syncActiveSlotKey();
            $reservation->assertActiveSlotIsAvailable();
        });

        static::creating(function (Reservation $reservation): void {
            if (blank($reservation->id_reff)) {
                $reservation->id_reff = app(ReservationReferenceService::class)->generate();
            }
        });

        static::created(function (Reservation $reservation): void {
            $reservation->syncReservationSlots();
            $reservation->syncResourceLocks();
            $reservation->syncReservationPriceLines();
        });

        static::updated(function (Reservation $reservation): void {
            $reservation->syncReservationSlots();
            $reservation->syncResourceLocks();
            $reservation->syncReservationPriceLines();
        });

        static::deleted(function (Reservation $reservation): void {
            if ($reservation->isForceDeleting()) {
                return;
            }

            $reservation->reservationSlots()->delete();
            $reservation->resourceLocks()->delete();

            if (static::masterTableExists('padelnis_reservation_price_lines')) {
                $reservation->reservationPriceLines()->delete();
            }

            static::query()
                ->withoutGlobalScopes()
                ->whereKey($reservation->getKey())
                ->update(['active_slot_key' => null]);

            $reservation->active_slot_key = null;
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [

            'reservation_date' => 'date',
            'expected_amount'  => 'decimal:2',
            'quoted_amount'    => 'decimal:2',
            'pricing_snapshot' => 'array',
            'transfer_amount'  => 'decimal:2',
            'transfer_date'    => 'date',
            'created_at'       => 'datetime',
            'updated_at'       => 'datetime',
            'deleted_at'       => 'datetime',
        ];
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function courtRecord(): BelongsTo
    {
        return $this->belongsTo(Court::class, 'court_id');
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    public function reservationSlots(): HasMany
    {
        return $this->hasMany(ReservationSlot::class);
    }

    public function resourceLocks(): HasMany
    {
        return $this->hasMany(ResourceLock::class);
    }

    public function reservationPriceLines(): HasMany
    {
        return $this->hasMany(ReservationPriceLine::class);
    }

    protected function performInsert(Builder $query): bool
    {
        for ($attempt = 1; $attempt <= self::REFERENCE_GENERATION_MAX_ATTEMPTS; $attempt++) {
            try {
                return $this->getConnection()->transaction(fn (): bool => parent::performInsert($query));
            } catch (QueryException $exception) {
                if (static::isDuplicateActiveSlotException($exception)) {
                    throw static::duplicateActiveSlotValidationException();
                }

                if (! $this->isDuplicateReferenceException($exception) || $attempt === self::REFERENCE_GENERATION_MAX_ATTEMPTS) {
                    throw $exception;
                }

                $this->id_reff = null;
                $this->exists = false;
                $this->wasRecentlyCreated = false;
            }
        }

        return false;
    }

    protected function performUpdate(Builder $query): bool
    {
        try {
            return $this->getConnection()->transaction(fn (): bool => parent::performUpdate($query));
        } catch (QueryException $exception) {
            if (static::isDuplicateActiveSlotException($exception)) {
                throw static::duplicateActiveSlotValidationException();
            }

            throw $exception;
        }
    }

    protected function isDuplicateReferenceException(QueryException $exception): bool
    {
        if (! static::isUniqueConstraintException($exception)) {
            return false;
        }

        $constraintMessage = static::constraintMessage($exception);

        return str_contains($constraintMessage, 'id_reff')
            || str_contains($constraintMessage, 'padelnis_reservations_id_reff_unique');
    }

    /**
     * @return array<string, string>
     */
    public static function transactionTypeOptions(): array
    {
        if (static::masterTableExists('padelnis_transaction_types')) {
            $transactionTypes = TransactionTypeMaster::options();

            if ($transactionTypes !== []) {
                return $transactionTypes;
            }
        }

        return TransactionType::options();
    }

    /**
     * @return array<string, string>
     */
    public static function courtOptions(): array
    {
        if (static::masterTableExists('padelnis_courts')) {
            $courts = Court::options();

            if ($courts !== []) {
                return $courts;
            }
        }

        $courts = array_values(config('padelnis.courts', []));

        return array_combine($courts, $courts) ?: [];
    }

    /**
     * @return array<int, string>
     */
    public static function coachOptions(): array
    {
        if (! static::masterTableExists('padelnis_coaches')) {
            return [];
        }

        return Coach::options();
    }

    /**
     * @return array<int, string>
     */
    public static function catalogItemOptions(mixed $transactionType): array
    {
        if (! static::masterTableExists('padelnis_catalog_items')) {
            return [];
        }

        return CatalogItem::optionsForTransactionType($transactionType);
    }

    /**
     * @return array<int, string>
     */
    public static function publicCatalogItemOptions(): array
    {
        if (! static::masterTableExists('padelnis_catalog_items')) {
            return [];
        }

        return CatalogItem::publicOptions();
    }

    public static function catalogItemRequiresCourt(mixed $catalogItemId): bool
    {
        return static::catalogItemFor($catalogItemId)?->requiresCourt() ?? true;
    }

    public static function catalogItemRequiresCoach(mixed $catalogItemId): bool
    {
        return static::catalogItemFor($catalogItemId)?->requiresCoach() ?? false;
    }

    public static function catalogItemUsesTimedResources(mixed $catalogItemId): bool
    {
        return static::catalogItemFor($catalogItemId)?->usesTimedResources() ?? true;
    }

    /**
     * @return array<string, string>
     */
    public static function slotOptions(): array
    {
        $slots = array_values(config('padelnis.slots', []));

        return array_combine($slots, $slots) ?: [];
    }

    /**
     * @return array<string, string>
     */
    public static function reservableTimeOptions(): array
    {
        $configuredSlots = static::configuredSlotRanges();
        $configuredMaxDurationHours = config('padelnis.max_duration_hours');
        $maxDurationHours = filled($configuredMaxDurationHours)
            ? max(1, (int) $configuredMaxDurationHours)
            : count($configuredSlots);
        $options = [];

        foreach ($configuredSlots as $startIndex => $startSlot) {
            $lastEndMinute = $startSlot['end'];

            for ($endIndex = $startIndex; $endIndex < count($configuredSlots); $endIndex++) {
                $duration = $endIndex - $startIndex + 1;
                $currentSlot = $configuredSlots[$endIndex];

                if ($duration > $maxDurationHours) {
                    break;
                }

                if ($endIndex > $startIndex && $currentSlot['start'] !== $lastEndMinute) {
                    break;
                }

                $lastEndMinute = $currentSlot['end'];
                $range = static::formatTimeRange($startSlot['start'], $currentSlot['end']);
                $options[$range] = $range;
            }
        }

        return $options;
    }

    public function setCustomerNameAttribute(mixed $value): void
    {
        $this->attributes['customer_name'] = $this->normalizeName($value);
    }

    public function setTransactionTypeAttribute(mixed $value): void
    {
        $this->attributes['transaction_type'] = TransactionTypeMaster::normalizeCode($value);
    }

    public function setCatalogItemIdAttribute(mixed $value): void
    {
        $this->attributes['catalog_item_id'] = blank($value) ? null : (int) $value;
    }

    public function setCourtIdAttribute(mixed $value): void
    {
        $this->attributes['court_id'] = blank($value) ? null : (int) $value;
    }

    public function setCourtAttribute(mixed $value): void
    {
        $this->attributes['court'] = $this->normalizeToConfiguredOption($value, static::courtOptions());
    }

    public function setCoachIdAttribute(mixed $value): void
    {
        $this->attributes['coach_id'] = blank($value) ? null : (int) $value;
    }

    public function setExpectedAmountAttribute(mixed $value): void
    {
        $this->attributes['expected_amount'] = static::normalizeTransferAmount($value);
    }

    public function setQuotedAmountAttribute(mixed $value): void
    {
        $this->attributes['quoted_amount'] = static::normalizeTransferAmount($value);
    }

    public function setTransferAmountAttribute(mixed $value): void
    {
        $this->attributes['transfer_amount'] = static::normalizeTransferAmount($value);
    }

    public function setNotesAttribute(mixed $value): void
    {
        $this->attributes['notes'] = blank($value) ? null : trim((string) $value);
    }

    public function setReservationTimeAttribute(mixed $value): void
    {
        $this->attributes['reservation_time'] = static::normalizeReservationTime($value);
    }

    public function getReservationTimeAttribute(mixed $value): string
    {
        return static::normalizeReservationTime($value);
    }

    public static function normalizeReservationTime(mixed $value): string
    {
        if (is_array($value)) {
            return static::normalizeReservationSlotsToRange($value);
        }

        $normalized = self::squishValue((string) $value);

        foreach (static::slotOptions() as $slot) {
            if (mb_strtolower($slot, 'UTF-8') === mb_strtolower($normalized, 'UTF-8')) {
                return $slot;
            }
        }

        if (! preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?(?:\s*-\s*(\d{1,2}):(\d{2})(?::\d{2})?)?$/', $normalized, $matches)) {
            return $normalized;
        }

        $startTime = sprintf('%02d:%s', (int) $matches[1], $matches[2]);
        $endTime = isset($matches[3], $matches[4])
            ? sprintf('%02d:%s', (int) $matches[3], $matches[4])
            : null;

        foreach (static::slotOptions() as $slot) {
            if ($endTime !== null && $slot === "{$startTime} - {$endTime}") {
                return $slot;
            }

            if ($endTime === null && str_starts_with($slot, "{$startTime} - ")) {
                return $slot;
            }
        }

        return $endTime === null ? $startTime : "{$startTime} - {$endTime}";
    }

    /**
     * @return list<string>
     */
    public static function slotValuesForReservationTime(mixed $value): array
    {
        if (is_array($value)) {
            $slots = array_map(fn (mixed $slot): string => static::normalizeReservationTime($slot), $value);
            $slots = array_values(array_unique(array_filter($slots, fn (string $slot): bool => array_key_exists($slot, static::slotOptions()))));
            $slotOrder = array_flip(array_keys(static::slotOptions()));

            usort($slots, fn (string $first, string $second): int => ($slotOrder[$first] ?? PHP_INT_MAX) <=> ($slotOrder[$second] ?? PHP_INT_MAX));

            return $slots;
        }

        $normalizedRange = static::normalizeReservationTime($value);
        $range = static::parseTimeRange($normalizedRange);

        if ($range === null) {
            return [];
        }

        return array_values(array_filter(static::slotOptions(), function (string $slot) use ($range): bool {
            $slotRange = static::parseTimeRange($slot);

            return $slotRange !== null
                && $slotRange['start'] >= $range['start']
                && $slotRange['end'] <= $range['end'];
        }));
    }

    /**
     * @return list<string>
     */
    public function blockedSlotLabels(): array
    {
        return static::slotValuesForReservationTime($this->reservation_time);
    }

    public function blockedSlotSummary(): string
    {
        return implode(', ', $this->blockedSlotLabels());
    }

    /**
     * @return list<string>
     */
    public function priceBreakdownLabels(): array
    {
        if (static::masterTableExists('padelnis_reservation_price_lines')) {
            $priceLines = $this->relationLoaded('reservationPriceLines')
                ? $this->reservationPriceLines
                : $this->reservationPriceLines()->get();

            if ($priceLines->isNotEmpty()) {
                return $priceLines
                    ->map(fn (ReservationPriceLine $line): string => $this->formatPriceBreakdownLabel(
                        $line->component,
                        $line->amount,
                        $line->slot,
                        $line->is_commissionable,
                    ))
                    ->values()
                    ->all();
            }
        }

        if (! is_array($this->pricing_snapshot) || ! is_array($this->pricing_snapshot['lines'] ?? null)) {
            return [];
        }

        return collect($this->pricing_snapshot['lines'])
            ->filter(fn (mixed $line): bool => is_array($line))
            ->map(fn (array $line): string => $this->formatPriceBreakdownLabel(
                $line['component'] ?? 'catalog_item',
                $line['amount'] ?? 0,
                $line['slot'] ?? null,
                (bool) ($line['is_commissionable'] ?? false),
            ))
            ->values()
            ->all();
    }

    protected function formatPriceBreakdownLabel(string $component, mixed $amount, ?string $slot, bool $isCommissionable): string
    {
        $componentLabel = CatalogItem::priceComponentOptions()[$component] ?? Str::headline(str_replace('_', ' ', $component));
        $amountLabel = 'Rp'.number_format((float) (static::normalizeTransferAmount($amount) ?? 0), 0, ',', '.');
        $slotLabel = filled($slot) ? " ({$slot})" : '';
        $commissionLabel = $isCommissionable
            ? ' '.__('padelnis::filament/resources/reservation.price_breakdown.commissionable')
            : '';

        return "{$componentLabel}{$slotLabel}: {$amountLabel}{$commissionLabel}";
    }

    public static function formatTransferAmountForForm(mixed $value): ?string
    {
        $normalized = static::normalizeTransferAmount($value);

        if ($normalized === null || $normalized === '') {
            return null;
        }

        if (! is_numeric($normalized)) {
            return (string) $value;
        }

        $amount = (float) $normalized;
        $decimalPlaces = floor($amount) === $amount ? 0 : 2;

        return number_format($amount, $decimalPlaces, ',', '.');
    }

    public static function normalizeTransferAmount(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return number_format((float) $value, 2, '.', '');
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $normalized = static::normalizeLocalizedNumber($value);

        return is_numeric($normalized)
            ? number_format((float) $normalized, 2, '.', '')
            : $normalized;
    }

    public function transactionTypeLabel(): string
    {
        if (static::masterTableExists('padelnis_transaction_types')) {
            return TransactionTypeMaster::labelFor($this->transaction_type);
        }

        $transactionType = TransactionTypeMaster::normalizeCode($this->transaction_type);

        return TransactionType::tryFrom($transactionType)?->label()
            ?? Str::headline(str_replace(['-', '_'], ' ', $transactionType));
    }

    public function catalogItemLabel(): ?string
    {
        return $this->catalogItem?->name;
    }

    public function coachLabel(): ?string
    {
        return $this->coach?->name;
    }

    public static function resolveExpectedAmount(mixed $catalogItemId, mixed $reservationDate = null, mixed $court = null, mixed $coachId = null, mixed $reservationTime = null): ?string
    {
        return static::resolvePricingQuote($catalogItemId, $reservationDate, $court, $coachId, $reservationTime)['total'] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function resolvePricingQuote(mixed $catalogItemId, mixed $reservationDate = null, mixed $court = null, mixed $coachId = null, mixed $reservationTime = null): ?array
    {
        $catalogItem = static::catalogItemFor($catalogItemId);

        if (! $catalogItem instanceof CatalogItem) {
            return null;
        }

        $courtId = static::resolveCourtId($court);
        $resolvedCoachId = blank($coachId) ? null : (int) $coachId;

        return app(PricingService::class)->quote($catalogItem, $reservationDate, $courtId, $resolvedCoachId, $reservationTime);
    }

    public static function makeActiveSlotKey(mixed $court, mixed $reservationDate, mixed $reservationTime): ?string
    {
        $normalizedCourt = static::normalizeToConfiguredOption($court, static::courtOptions());
        $normalizedDate = static::normalizeReservationDate($reservationDate);
        $normalizedTime = static::normalizeReservationTime($reservationTime);

        if ($normalizedCourt === '' || $normalizedDate === null || $normalizedTime === '') {
            return null;
        }

        return "{$normalizedCourt}|{$normalizedDate}|{$normalizedTime}";
    }

    /**
     * @return list<string>
     */
    public static function activeSlotKeys(mixed $court, mixed $reservationDate, mixed $reservationTime): array
    {
        $slotKeys = [];

        foreach (static::slotValuesForReservationTime($reservationTime) as $slot) {
            $slotKey = static::makeActiveSlotKey($court, $reservationDate, $slot);

            if ($slotKey !== null) {
                $slotKeys[] = $slotKey;
            }
        }

        return array_values(array_unique($slotKeys));
    }

    public static function activeSlotExists(
        mixed $court,
        mixed $reservationDate,
        mixed $reservationTime,
        mixed $ignoredKey = null,
        mixed $catalogItemId = null,
        mixed $coachId = null,
        mixed $transactionType = null,
    ): bool {
        if (static::masterTableExists('padelnis_resource_locks')) {
            $resourceLockKeys = static::resourceLockKeysFor($catalogItemId, $court, $coachId, $reservationDate, $reservationTime, $transactionType);

            if ($resourceLockKeys !== []) {
                $resourceLockQuery = ResourceLock::query()
                    ->whereIn('active_lock_key', $resourceLockKeys);

                if ($ignoredKey !== null) {
                    $resourceLockQuery->where('reservation_id', '!=', $ignoredKey);
                }

                if ($resourceLockQuery->exists()) {
                    return true;
                }
            }
        }

        $activeSlotKeys = static::activeSlotKeys($court, $reservationDate, $reservationTime);

        if ($activeSlotKeys === []) {
            return false;
        }

        $slotQuery = ReservationSlot::query()
            ->whereIn('active_slot_key', $activeSlotKeys);

        if ($ignoredKey !== null) {
            $slotQuery->where('reservation_id', '!=', $ignoredKey);
        }

        if ($slotQuery->exists()) {
            return true;
        }

        $reservationQuery = static::query()
            ->withoutGlobalScopes()
            ->whereIn('active_slot_key', $activeSlotKeys);

        if ($ignoredKey !== null) {
            $reservationQuery->whereKeyNot($ignoredKey);
        }

        return $reservationQuery->exists();
    }

    /**
     * @return list<string>
     */
    public static function resourceLockKeysFor(
        mixed $catalogItemId,
        mixed $court,
        mixed $coachId,
        mixed $reservationDate,
        mixed $reservationTime,
        mixed $transactionType = null,
    ): array {
        $attributes = static::resourceLockAttributesFor($catalogItemId, $court, $coachId, $reservationDate, $reservationTime, $transactionType);

        return array_values(array_unique(array_column($attributes, 'active_lock_key')));
    }

    public static function isDuplicateActiveSlotException(QueryException $exception): bool
    {
        if (! static::isUniqueConstraintException($exception)) {
            return false;
        }

        $constraintMessage = static::constraintMessage($exception);

        return str_contains($constraintMessage, 'active_slot_key')
            || str_contains($constraintMessage, 'active_lock_key')
            || str_contains($constraintMessage, 'padelnis_reservations_active_slot_key_unique')
            || str_contains($constraintMessage, 'padelnis_resource_locks_active_lock_key_unique');
    }

    protected static function isUniqueConstraintException(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true);
    }

    protected static function constraintMessage(QueryException $exception): string
    {
        return strtolower((string) ($exception->errorInfo[2] ?? $exception->getPrevious()?->getMessage() ?? $exception->getMessage()));
    }

    protected static function duplicateActiveSlotValidationException(): ValidationException
    {
        return ValidationException::withMessages([
            'reservation_time' => __('padelnis::filament/resources/reservation.validation.active_slot_unique'),
        ]);
    }

    protected function syncTransactionDefaults(): void
    {
        $catalogItem = static::catalogItemFor($this->catalog_item_id);

        if ($catalogItem instanceof CatalogItem) {
            $this->transaction_type = $catalogItem->transaction_type;

            if (! $catalogItem->requiresCoach()) {
                $this->coach_id = null;
            }

            if (! $catalogItem->requiresCourt()) {
                $this->court_id = null;
                $this->court = '';
            }

            if (! $catalogItem->usesTimedResources()) {
                $this->reservation_time = '';
            }

            return;
        }

        $this->transaction_type = TransactionTypeMaster::normalizeCode($this->transaction_type ?? TransactionType::Regular);
        $requiresCoach = static::masterTableExists('padelnis_transaction_types')
            ? TransactionTypeMaster::requiresCoach($this->transaction_type)
            : (TransactionType::tryFrom($this->transaction_type)?->requiresCoach() ?? false);

        if (! $requiresCoach) {
            $this->coach_id = null;
        }
    }

    protected function syncCourtIdFromName(): void
    {
        if (blank($this->court)) {
            $this->court_id = null;

            return;
        }

        if (filled($this->court_id) && ! $this->isDirty('court')) {
            return;
        }

        $this->court_id = static::resolveCourtId($this->court);
    }

    protected function syncExpectedAmount(): void
    {
        if (blank($this->catalog_item_id)) {
            $this->expected_amount = null;
            $this->quoted_amount = null;
            $this->pricing_snapshot = null;

            return;
        }

        if (filled($this->expected_amount)
            && filled($this->quoted_amount)
            && filled($this->pricing_snapshot)
            && ! $this->isDirty(['catalog_item_id', 'reservation_date', 'court_id', 'court', 'coach_id', 'reservation_time', 'expected_amount', 'quoted_amount'])
        ) {
            return;
        }

        $quote = static::resolvePricingQuote(
            $this->catalog_item_id,
            $this->reservation_date,
            $this->court_id ?? $this->court,
            $this->coach_id,
            $this->reservation_time,
        );

        $this->expected_amount = $quote['total'] ?? null;
        $this->quoted_amount = $quote['total'] ?? null;
        $this->pricing_snapshot = $quote;
    }

    protected function assertPricingQuoteIsConfigured(): void
    {
        if (! is_array($this->pricing_snapshot) || ! is_array($this->pricing_snapshot['lines'] ?? null)) {
            return;
        }

        $missingComponents = collect($this->pricing_snapshot['lines'])
            ->filter(fn (mixed $line): bool => is_array($line) && ($line['source'] ?? null) === 'missing_price_rule')
            ->pluck('component')
            ->unique()
            ->values()
            ->all();

        if ($missingComponents === []) {
            return;
        }

        $labels = collect($missingComponents)
            ->map(fn (string $component): string => CatalogItem::priceComponentOptions()[$component] ?? Str::headline($component))
            ->implode(', ');

        throw ValidationException::withMessages([
            'catalog_item_id' => __('padelnis::filament/resources/reservation.validation.pricing_rules_missing', [
                'components' => $labels,
            ]),
        ]);
    }

    protected function assertTransactionRequirements(): void
    {
        $transactionType = TransactionTypeMaster::normalizeCode($this->transaction_type);
        $catalogItem = static::catalogItemFor($this->catalog_item_id);

        if ($catalogItem instanceof CatalogItem) {
            if ($catalogItem->transaction_type !== $transactionType) {
                throw ValidationException::withMessages([
                    'catalog_item_id' => __('padelnis::filament/resources/reservation.validation.catalog_item_transaction_type'),
                ]);
            }

            if ($catalogItem->requiresCourt() && blank($this->court_id) && blank($this->court)) {
                throw ValidationException::withMessages([
                    'court' => __('padelnis::filament/resources/reservation.validation.court_required'),
                ]);
            }

            if ($catalogItem->requiresCoach() && blank($this->coach_id)) {
                throw ValidationException::withMessages([
                    'coach_id' => __('padelnis::filament/resources/reservation.validation.coach_required'),
                ]);
            }

            if ($catalogItem->usesTimedResources() && blank($this->reservation_time)) {
                throw ValidationException::withMessages([
                    'reservation_time' => __('padelnis::filament/resources/reservation.validation.reservation_time_required'),
                ]);
            }

            if ($catalogItem->usesTimedResources() && static::slotValuesForReservationTime($this->reservation_time) === []) {
                throw ValidationException::withMessages([
                    'reservation_time' => __('padelnis::filament/resources/reservation.validation.reservation_time_invalid'),
                ]);
            }

            return;
        }

        $requiresCoach = static::masterTableExists('padelnis_transaction_types')
            ? TransactionTypeMaster::requiresCoach($transactionType)
            : (TransactionType::tryFrom($transactionType)?->requiresCoach() ?? false);
        $requiresCatalogItem = static::masterTableExists('padelnis_transaction_types')
            ? TransactionTypeMaster::requiresCatalogItem($transactionType)
            : (TransactionType::tryFrom($transactionType)?->requiresCatalogItem() ?? false);

        if ($requiresCoach && blank($this->coach_id)) {
            throw ValidationException::withMessages([
                'coach_id' => __('padelnis::filament/resources/reservation.validation.coach_required'),
            ]);
        }

        if ($requiresCatalogItem && blank($this->catalog_item_id)) {
            throw ValidationException::withMessages([
                'catalog_item_id' => __('padelnis::filament/resources/reservation.validation.catalog_item_required'),
            ]);
        }
    }

    protected function syncActiveSlotKey(): void
    {
        if (! $this->shouldBlockResources()) {
            $this->active_slot_key = null;

            return;
        }

        $activeSlotKeys = static::activeSlotKeys($this->court, $this->reservation_date, $this->reservation_time);

        $this->active_slot_key = $this->deleted_at
            ? null
            : ($activeSlotKeys[0] ?? null);
    }

    protected function assertActiveSlotIsAvailable(): void
    {
        if (! $this->shouldBlockResources()) {
            return;
        }

        if (static::masterTableExists('padelnis_resource_locks')) {
            $resourceLockKeys = $this->resourceLockKeys();

            if ($resourceLockKeys !== []) {
                $resourceLockQuery = ResourceLock::query()
                    ->whereIn('active_lock_key', $resourceLockKeys);

                if ($this->exists && $this->getKey() !== null) {
                    $resourceLockQuery->where('reservation_id', '!=', $this->getKey());
                }

                if ($resourceLockQuery->exists()) {
                    throw static::duplicateActiveSlotValidationException();
                }
            }
        }

        $activeSlotKeys = static::activeSlotKeys($this->court, $this->reservation_date, $this->reservation_time);

        if ($activeSlotKeys === []) {
            return;
        }

        $slotQuery = ReservationSlot::query()
            ->whereIn('active_slot_key', $activeSlotKeys);

        if ($this->exists && $this->getKey() !== null) {
            $slotQuery->where('reservation_id', '!=', $this->getKey());
        }

        if ($slotQuery->exists()) {
            throw static::duplicateActiveSlotValidationException();
        }
    }

    protected function syncReservationSlots(): void
    {
        if (! $this->shouldBlockResources()) {
            $this->reservationSlots()->delete();

            return;
        }

        $this->reservationSlots()->delete();

        foreach (static::activeSlotKeys($this->court, $this->reservation_date, $this->reservation_time) as $activeSlotKey) {
            $this->reservationSlots()->create([
                'active_slot_key' => $activeSlotKey,
            ]);
        }
    }

    protected function syncResourceLocks(): void
    {
        if (! static::masterTableExists('padelnis_resource_locks')) {
            return;
        }

        $this->resourceLocks()->delete();

        if (! $this->shouldBlockResources()) {
            return;
        }

        foreach ($this->resourceLockAttributes() as $attributes) {
            $this->resourceLocks()->create($attributes);
        }
    }

    protected function syncReservationPriceLines(): void
    {
        if (! static::masterTableExists('padelnis_reservation_price_lines')) {
            return;
        }

        $this->reservationPriceLines()->delete();

        foreach ($this->reservationPriceLineAttributes() as $attributes) {
            $this->reservationPriceLines()->create($attributes);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function reservationPriceLineAttributes(): array
    {
        if (! is_array($this->pricing_snapshot) || ! is_array($this->pricing_snapshot['lines'] ?? null)) {
            return [];
        }

        return collect($this->pricing_snapshot['lines'])
            ->filter(fn (mixed $line): bool => is_array($line))
            ->map(fn (array $line): array => $this->reservationPriceLineAttribute($line))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function reservationPriceLineAttribute(array $line): array
    {
        $basis = $line['basis'] ?? null;
        $rate = isset($line['rate']) && is_numeric($line['rate']) ? (float) $line['rate'] : null;
        $basisAmount = $line['basis_amount'] ?? $line['amount'] ?? null;
        $amount = $line['amount'] ?? 0;

        if ($basis === CatalogItem::BASIS_TRANSFER_AMOUNT && $rate !== null && is_numeric($this->transfer_amount)) {
            $basisAmount = $this->transfer_amount;
            $amount = ((float) $this->transfer_amount * $rate) / 100;
        }

        return [
            'catalog_item_id'   => blank($line['catalog_item_id'] ?? null) ? null : (int) $line['catalog_item_id'],
            'component'         => $line['component'] ?? 'catalog_item',
            'slot'              => $line['slot'] ?? null,
            'calculation_type'  => $line['calculation_type'] ?? null,
            'basis'             => $basis,
            'basis_amount'      => static::normalizeTransferAmount($basisAmount),
            'rate'              => $rate,
            'amount'            => static::normalizeTransferAmount($amount) ?? '0.00',
            'source'            => $line['source'] ?? null,
            'price_rule_id'     => blank($line['rule_id'] ?? null) ? null : (int) $line['rule_id'],
            'is_commissionable' => (bool) ($line['is_commissionable'] ?? false),
            'metadata'          => $line,
        ];
    }

    protected function shouldBlockResources(): bool
    {
        return ! $this->trashed();
    }

    /**
     * @return list<string>
     */
    protected function resourceLockKeys(): array
    {
        return static::resourceLockKeysFor(
            $this->catalog_item_id,
            $this->court_id ?? $this->court,
            $this->coach_id,
            $this->reservation_date,
            $this->reservation_time,
            $this->transaction_type,
        );
    }

    /**
     * @return list<array{resource_type: string, resource_id: int, lock_date: string, slot: string, active_lock_key: string}>
     */
    protected function resourceLockAttributes(): array
    {
        return static::resourceLockAttributesFor(
            $this->catalog_item_id,
            $this->court_id ?? $this->court,
            $this->coach_id,
            $this->reservation_date,
            $this->reservation_time,
            $this->transaction_type,
        );
    }

    /**
     * @return list<array{resource_type: string, resource_id: int, lock_date: string, slot: string, active_lock_key: string}>
     */
    public static function resourceLockAttributesFor(
        mixed $catalogItemId,
        mixed $court,
        mixed $coachId,
        mixed $reservationDate,
        mixed $reservationTime,
        mixed $transactionType = null,
    ): array {
        $date = static::normalizeReservationDate($reservationDate);
        $slots = static::slotValuesForReservationTime($reservationTime);

        if ($date === null || $slots === []) {
            return [];
        }

        $catalogItem = static::catalogItemFor($catalogItemId);
        $requiresCourt = $catalogItem?->requiresCourt() ?? true;
        $requiresCoach = $catalogItem?->requiresCoach() ?? static::transactionTypeRequiresCoach($transactionType);
        $attributes = [];

        if ($requiresCourt) {
            $courtId = static::resolveCourtId($court);

            if ($courtId !== null) {
                foreach ($slots as $slot) {
                    $lockKey = ResourceLock::makeActiveLockKey(ResourceLock::RESOURCE_COURT, $courtId, $date, $slot);

                    if ($lockKey !== null) {
                        $attributes[] = [
                            'resource_type'   => ResourceLock::RESOURCE_COURT,
                            'resource_id'     => $courtId,
                            'lock_date'       => $date,
                            'slot'            => $slot,
                            'active_lock_key' => $lockKey,
                        ];
                    }
                }
            }
        }

        if ($requiresCoach && filled($coachId)) {
            foreach ($slots as $slot) {
                $lockKey = ResourceLock::makeActiveLockKey(ResourceLock::RESOURCE_COACH, (int) $coachId, $date, $slot);

                if ($lockKey !== null) {
                    $attributes[] = [
                        'resource_type'   => ResourceLock::RESOURCE_COACH,
                        'resource_id'     => (int) $coachId,
                        'lock_date'       => $date,
                        'slot'            => $slot,
                        'active_lock_key' => $lockKey,
                    ];
                }
            }
        }

        return $attributes;
    }

    protected static function transactionTypeRequiresCoach(mixed $transactionType): bool
    {
        if (blank($transactionType)) {
            return false;
        }

        $transactionType = TransactionTypeMaster::normalizeCode($transactionType);

        return static::masterTableExists('padelnis_transaction_types')
            ? TransactionTypeMaster::requiresCoach($transactionType)
            : (TransactionType::tryFrom($transactionType)?->requiresCoach() ?? false);
    }

    protected static function normalizeReservationDate(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        $normalized = self::squishValue((string) $value);

        if ($normalized === '') {
            return null;
        }

        try {
            return Carbon::parse($normalized)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public static function normalizeReservationDateForLock(mixed $value): ?string
    {
        return static::normalizeReservationDate($value);
    }

    protected static function normalizeLocalizedNumber(string $value): string
    {
        $value = preg_replace('/[^\d,.\-]/', '', $value) ?? '';

        if ($value === '') {
            return '';
        }

        $isNegative = str_starts_with($value, '-');
        $value = str_replace('-', '', $value);

        $lastCommaPosition = strrpos($value, ',');
        $lastDotPosition = strrpos($value, '.');
        $decimalSeparator = null;

        if ($lastCommaPosition !== false && $lastDotPosition !== false) {
            $decimalSeparator = $lastCommaPosition > $lastDotPosition ? ',' : '.';
        } elseif ($lastCommaPosition !== false) {
            $fractionLength = strlen($value) - $lastCommaPosition - 1;
            $decimalSeparator = $fractionLength > 0 && $fractionLength <= 2 ? ',' : null;
        } elseif ($lastDotPosition !== false) {
            $fractionLength = strlen($value) - $lastDotPosition - 1;
            $decimalSeparator = $fractionLength > 0 && $fractionLength <= 2 ? '.' : null;
        }

        if ($decimalSeparator === null) {
            $normalized = preg_replace('/\D/', '', $value) ?? '';

            return $isNegative && $normalized !== '' ? "-{$normalized}" : $normalized;
        }

        $separatorPosition = strrpos($value, $decimalSeparator);
        $integer = preg_replace('/\D/', '', substr($value, 0, $separatorPosition)) ?: '0';
        $fraction = preg_replace('/\D/', '', substr($value, $separatorPosition + 1)) ?? '';
        $normalized = "{$integer}.{$fraction}";

        return $isNegative ? "-{$normalized}" : $normalized;
    }

    /**
     * @param  array<mixed>  $slots
     */
    protected static function normalizeReservationSlotsToRange(array $slots): string
    {
        $selectedSlots = static::slotValuesForReservationTime($slots);

        if ($selectedSlots === []) {
            return '';
        }

        $ranges = array_values(array_filter(
            array_map(fn (string $slot): ?array => static::parseTimeRange($slot), $selectedSlots),
        ));

        if ($ranges === []) {
            return '';
        }

        return static::formatTimeRange($ranges[0]['start'], $ranges[array_key_last($ranges)]['end']);
    }

    /**
     * @return list<array{value: string, start: int, end: int}>
     */
    protected static function configuredSlotRanges(): array
    {
        $ranges = [];

        foreach (static::slotOptions() as $slot) {
            $range = static::parseTimeRange($slot);

            if ($range === null) {
                continue;
            }

            $ranges[] = [
                'value' => $slot,
                'start' => $range['start'],
                'end'   => $range['end'],
            ];
        }

        usort($ranges, fn (array $first, array $second): int => $first['start'] <=> $second['start']);

        return $ranges;
    }

    /**
     * @return array{start: int, end: int}|null
     */
    protected static function parseTimeRange(string $value): ?array
    {
        $normalized = self::squishValue($value);

        if (! preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?(?:\s*-\s*(\d{1,2}):(\d{2})(?::\d{2})?)?$/', $normalized, $matches)) {
            return null;
        }

        $start = (((int) $matches[1]) * 60) + ((int) $matches[2]);
        $end = isset($matches[3], $matches[4])
            ? (((int) $matches[3]) * 60) + ((int) $matches[4])
            : null;

        if ($end === null) {
            foreach (static::configuredSlotRanges() as $slot) {
                if ($slot['start'] === $start) {
                    $end = $slot['end'];

                    break;
                }
            }
        }

        if ($end === null || $end <= $start) {
            return null;
        }

        return [
            'start' => $start,
            'end'   => $end,
        ];
    }

    protected static function formatTimeRange(int $start, int $end): string
    {
        return sprintf('%s - %s', static::formatTime($start), static::formatTime($end));
    }

    protected static function formatTime(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    protected function normalizeName(mixed $value): string
    {
        return static::normalizeDisplayName($value);
    }

    public static function normalizeDisplayName(mixed $value): string
    {
        $normalized = self::squishValue((string) $value);

        return mb_convert_case($normalized, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * @param  array<string, string>  $options
     */
    protected static function normalizeToConfiguredOption(mixed $value, array $options): string
    {
        $normalized = self::squishValue((string) $value);

        foreach ($options as $option) {
            if (mb_strtolower($option, 'UTF-8') === mb_strtolower($normalized, 'UTF-8')) {
                return $option;
            }
        }

        return $normalized;
    }

    protected static function squishValue(string $value): string
    {
        $squished = preg_replace('/\s+/', ' ', trim($value));

        return is_string($squished) ? $squished : trim($value);
    }

    protected static function resolveCourtId(mixed $court): ?int
    {
        if (is_numeric($court)) {
            return (int) $court;
        }

        if (! static::masterTableExists('padelnis_courts')) {
            return null;
        }

        $normalizedCourt = static::normalizeToConfiguredOption($court, static::courtOptions());

        return Court::query()
            ->where('name', $normalizedCourt)
            ->value('id');
    }

    public static function extractFirstSlot(mixed $reservationTime): ?string
    {
        if (blank($reservationTime)) {
            return null;
        }

        $slots = static::slotValuesForReservationTime($reservationTime);

        return $slots[0] ?? null;
    }

    protected static function catalogItemFor(mixed $catalogItemId): ?CatalogItem
    {
        if (blank($catalogItemId) || ! static::masterTableExists('padelnis_catalog_items')) {
            return null;
        }

        $catalogItem = CatalogItem::query()->find($catalogItemId);

        return $catalogItem instanceof CatalogItem ? $catalogItem : null;
    }

    protected static function masterTableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    protected static function newFactory(): ReservationFactory
    {
        return ReservationFactory::new();
    }
}
