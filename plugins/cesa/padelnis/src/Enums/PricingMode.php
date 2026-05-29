<?php

namespace Cesa\Padelnis\Enums;

enum PricingMode: string
{
    case PerSlot = 'per_slot';
    case Fixed = 'fixed';

    public function label(): string
    {
        return __("padelnis::filament/resources/catalog-item.pricing_modes.{$this->value}");
    }

    public function isPerSlot(): bool
    {
        return $this === self::PerSlot;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $mode): array => [$mode->value => $mode->label()])
            ->all();
    }

    public static function fromValue(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::tryFrom((string) $value) ?? self::PerSlot;
    }
}
