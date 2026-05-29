<?php

namespace Cesa\Padelnis\Services;

use Cesa\Padelnis\Enums\PricingMode;
use Cesa\Padelnis\Enums\TransactionType as TransactionTypeCode;
use Cesa\Padelnis\Models\CatalogItem;
use Cesa\Padelnis\Models\Reservation;
use Cesa\Padelnis\Models\SpecialPrice;
use Cesa\Padelnis\Models\TransactionType;

class PricingService
{
    /**
     * @return array{
     *     catalog_item_id: int,
     *     pricing_mode: string,
     *     total: string,
     *     currency: string,
     *     lines: list<array{component: string, catalog_item_id: int, catalog_item: string, slot: ?string, amount: string, source: string, rule_id: ?int}>
     * }
     */
    public function quote(
        CatalogItem $catalogItem,
        mixed $reservationDate = null,
        ?int $courtId = null,
        ?int $coachId = null,
        mixed $reservationTime = null,
    ): array {
        $pricingMode = PricingMode::fromValue($catalogItem->pricing_mode ?? PricingMode::PerSlot);

        if ($catalogItem->hasPriceComponents()) {
            return $this->makeQuote(
                $catalogItem,
                $pricingMode,
                $this->componentLinesFor($catalogItem, $reservationDate, $courtId, $coachId, $reservationTime),
            );
        }

        if ($this->shouldAddCourtPrice($catalogItem)) {
            $courtCatalogItem = $this->courtCatalogItem();

            if ($courtCatalogItem instanceof CatalogItem) {
                return $this->makeQuote($catalogItem, $pricingMode, [
                    ...$this->linesFor($courtCatalogItem, $reservationDate, $courtId, null, $reservationTime, 'court'),
                    ...$this->linesFor($catalogItem, $reservationDate, $courtId, $coachId, $reservationTime, 'coach'),
                ]);
            }
        }

        return $this->makeQuote(
            $catalogItem,
            $pricingMode,
            $this->linesFor($catalogItem, $reservationDate, $courtId, $coachId, $reservationTime, 'catalog_item'),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function componentLinesFor(
        CatalogItem $catalogItem,
        mixed $reservationDate,
        ?int $courtId,
        ?int $coachId,
        mixed $reservationTime,
    ): array {
        $pricingMode = PricingMode::fromValue($catalogItem->pricing_mode ?? PricingMode::PerSlot);
        $slots = Reservation::slotValuesForReservationTime($reservationTime);

        if ($slots === [] || ! $pricingMode->isPerSlot()) {
            return $this->componentLinesForSlot($catalogItem, $reservationDate, $courtId, $coachId, $slots[0] ?? null);
        }

        return collect($slots)
            ->flatMap(fn (string $slot): array => $this->componentLinesForSlot($catalogItem, $reservationDate, $courtId, $coachId, $slot))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function componentLinesForSlot(
        CatalogItem $catalogItem,
        mixed $reservationDate,
        ?int $courtId,
        ?int $coachId,
        ?string $slot,
    ): array {
        $baseLine = $this->lineFor($catalogItem, $reservationDate, $courtId, $coachId, $slot, 'catalog_item');
        $baseAmount = (float) $baseLine['amount'];

        return collect($catalogItem->priceComponents())
            ->map(function (array $component) use ($catalogItem, $reservationDate, $courtId, $coachId, $slot, $baseAmount): array {
                $rule = SpecialPrice::currentFor($catalogItem, $reservationDate, $courtId, $coachId, $slot, $component['component']);

                if ($rule instanceof SpecialPrice) {
                    return $this->componentLineForRule($catalogItem, $component, $rule, $slot, $baseAmount);
                }

                $sourceCatalogItem = $this->sourceCatalogItemFor($component, $catalogItem);
                $calculationType = $component['calculation_type'];
                $basisAmount = $baseAmount;
                $amount = 0.0;
                $source = $calculationType === null ? 'missing_price_rule' : "price_component_{$calculationType}";
                $ruleId = null;

                if ($calculationType === CatalogItem::CALCULATION_FIXED) {
                    $amount = (float) $component['amount'];
                    $basisAmount = $amount;
                }

                if ($calculationType === CatalogItem::CALCULATION_PERCENTAGE) {
                    $amount = ($baseAmount * (float) $component['percentage']) / 100;
                }

                if ($calculationType === CatalogItem::CALCULATION_CATALOG_ITEM) {
                    $sourceLine = $this->lineFor($sourceCatalogItem, $reservationDate, $courtId, $coachId, $slot, $component['component']);
                    $amount = (float) $sourceLine['amount'];
                    $basisAmount = $amount;
                    $source = $sourceLine['source'];
                    $ruleId = $sourceLine['rule_id'];
                }

                return [
                    'component'              => $component['component'],
                    'catalog_item_id'        => (int) $sourceCatalogItem->getKey(),
                    'catalog_item'           => $sourceCatalogItem->name,
                    'slot'                   => $slot,
                    'amount'                 => $this->normalizeAmount($amount),
                    'source'                 => $source,
                    'rule_id'                => $ruleId,
                    'calculation_type'       => $calculationType,
                    'basis'                  => $component['basis'],
                    'basis_amount'           => $this->normalizeAmount($basisAmount),
                    'rate'                   => $component['percentage'] ?? null,
                    'source_catalog_item_id' => $component['source_catalog_item_id'],
                    'is_commissionable'      => $component['is_commissionable'],
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{component: string, catalog_item_id: int, catalog_item: string, slot: ?string, amount: string, source: string, rule_id: ?int}>
     */
    protected function linesFor(
        CatalogItem $catalogItem,
        mixed $reservationDate,
        ?int $courtId,
        ?int $coachId,
        mixed $reservationTime,
        string $component,
    ): array {
        $pricingMode = PricingMode::fromValue($catalogItem->pricing_mode ?? PricingMode::PerSlot);
        $slots = Reservation::slotValuesForReservationTime($reservationTime);

        if ($slots === [] || ! $pricingMode->isPerSlot()) {
            return [
                $this->lineFor($catalogItem, $reservationDate, $courtId, $coachId, $slots[0] ?? null, $component),
            ];
        }

        return array_map(
            fn (string $slot): array => $this->lineFor($catalogItem, $reservationDate, $courtId, $coachId, $slot, $component),
            $slots,
        );
    }

    /**
     * @return array{component: string, catalog_item_id: int, catalog_item: string, slot: ?string, amount: string, source: string, rule_id: ?int}
     */
    protected function lineFor(
        CatalogItem $catalogItem,
        mixed $reservationDate,
        ?int $courtId,
        ?int $coachId,
        ?string $slot,
        string $component,
    ): array {
        $rule = SpecialPrice::currentFor($catalogItem, $reservationDate, $courtId, $coachId, $slot);
        $amount = $rule?->amount ?? $catalogItem->price_amount;

        return [
            'component'       => $component,
            'catalog_item_id' => (int) $catalogItem->getKey(),
            'catalog_item'    => $catalogItem->name,
            'slot'            => $slot,
            'amount'          => $this->normalizeAmount($amount),
            'source'          => $rule instanceof SpecialPrice ? 'special_price' : 'base_price',
            'rule_id'         => $rule?->getKey(),
        ];
    }

    /**
     * @param  array<string, mixed>  $component
     * @return array<string, mixed>
     */
    protected function componentLineForRule(
        CatalogItem $catalogItem,
        array $component,
        SpecialPrice $rule,
        ?string $slot,
        float $baseAmount,
    ): array {
        $calculationType = $rule->calculation_type ?? CatalogItem::CALCULATION_FIXED;
        $basis = $rule->basis ?? CatalogItem::BASIS_QUOTED_AMOUNT;
        $basisAmount = $baseAmount;
        $rate = null;
        $amount = (float) $rule->amount;

        if ($calculationType === CatalogItem::CALCULATION_PERCENTAGE) {
            $rate = (float) ($rule->percentage ?? 0);
            $amount = ($baseAmount * $rate) / 100;
        } else {
            $calculationType = CatalogItem::CALCULATION_FIXED;
            $basisAmount = $amount;
        }

        return [
            'component'              => $component['component'],
            'catalog_item_id'        => (int) $catalogItem->getKey(),
            'catalog_item'           => $catalogItem->name,
            'slot'                   => $slot,
            'amount'                 => $this->normalizeAmount($amount),
            'source'                 => 'special_price_component',
            'rule_id'                => $rule->getKey(),
            'calculation_type'       => $calculationType,
            'basis'                  => $basis,
            'basis_amount'           => $this->normalizeAmount($basisAmount),
            'rate'                   => $rate,
            'source_catalog_item_id' => null,
            'is_commissionable'      => $component['is_commissionable'],
        ];
    }

    protected function sourceCatalogItemFor(array $component, CatalogItem $fallback): CatalogItem
    {
        if (blank($component['source_catalog_item_id'] ?? null)) {
            return $fallback;
        }

        return CatalogItem::query()->find($component['source_catalog_item_id']) ?? $fallback;
    }

    /**
     * @param  list<array{component: string, catalog_item_id: int, catalog_item: string, slot: ?string, amount: string, source: string, rule_id: ?int}>  $lines
     * @return array{
     *     catalog_item_id: int,
     *     pricing_mode: string,
     *     total: string,
     *     currency: string,
     *     lines: list<array{component: string, catalog_item_id: int, catalog_item: string, slot: ?string, amount: string, source: string, rule_id: ?int}>
     * }
     */
    protected function makeQuote(CatalogItem $catalogItem, PricingMode $pricingMode, array $lines): array
    {
        $total = array_reduce(
            $lines,
            fn (float $sum, array $line): float => $sum + (float) $line['amount'],
            0.0,
        );

        return [
            'catalog_item_id' => (int) $catalogItem->getKey(),
            'pricing_mode'    => $pricingMode->value,
            'total'           => $this->normalizeAmount($total),
            'currency'        => 'IDR',
            'lines'           => $lines,
        ];
    }

    protected function shouldAddCourtPrice(CatalogItem $catalogItem): bool
    {
        return TransactionType::normalizeCode($catalogItem->transaction_type) === TransactionTypeCode::Coaching->value
            && $catalogItem->requiresCourt()
            && $catalogItem->requiresCoach();
    }

    protected function courtCatalogItem(): ?CatalogItem
    {
        return CatalogItem::query()
            ->active()
            ->where('transaction_type', TransactionTypeCode::Regular->value)
            ->where('requires_court', true)
            ->where('requires_coach', false)
            ->orderByRaw('CASE WHEN name = ? THEN 0 ELSE 1 END', ['Regular Court Rental'])
            ->orderBy('sort')
            ->orderBy('name')
            ->first();
    }

    protected function normalizeAmount(mixed $amount): string
    {
        return number_format((float) ($amount ?? 0), 2, '.', '');
    }
}
