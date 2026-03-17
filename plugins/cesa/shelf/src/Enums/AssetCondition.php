<?php

namespace Cesa\Shelf\Enums;

enum AssetCondition: string
{
    case Available = 'available';
    case Transferred = 'transferred';
    case Lost = 'lost';
    case Damaged = 'damaged';

    public function label(): string
    {
        return match ($this) {
            self::Available   => __('shelf::enums.asset-condition.available'),
            self::Transferred => __('shelf::enums.asset-condition.transferred'),
            self::Lost        => __('shelf::enums.asset-condition.lost'),
            self::Damaged     => __('shelf::enums.asset-condition.damaged'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available   => 'success',
            self::Transferred => 'warning',
            self::Lost        => 'danger',
            self::Damaged     => 'danger',
        };
    }

    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
