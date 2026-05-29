<?php

namespace Cesa\Padelnis\Enums;

enum TransactionType: string
{
    case Regular = 'regular';
    case Coaching = 'coaching';
    case Academy = 'academy';

    public function label(): string
    {
        return __("padelnis::filament/resources/reservation.transaction_types.{$this->value}");
    }

    public function requiresCoach(): bool
    {
        return $this === self::Coaching;
    }

    public function requiresCatalogItem(): bool
    {
        return $this === self::Academy;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }

    public static function fromValue(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        return self::tryFrom((string) $value) ?? self::Regular;
    }
}
