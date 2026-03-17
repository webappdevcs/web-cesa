<?php

namespace Cesa\Shelf\Enums;

enum NbhStatus: string
{
    case None = 'none';
    case Pending = 'pending';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::None     => __('shelf::enums.nbh-status.none'),
            self::Pending  => __('shelf::enums.nbh-status.pending'),
            self::Resolved => __('shelf::enums.nbh-status.resolved'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::None     => 'secondary',
            self::Pending  => 'warning',
            self::Resolved => 'success',
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
