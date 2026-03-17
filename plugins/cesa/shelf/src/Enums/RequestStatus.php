<?php

namespace Cesa\Shelf\Enums;

enum RequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => __('shelf::enums.request-status.pending'),
            self::Approved => __('shelf::enums.request-status.approved'),
            self::Rejected => __('shelf::enums.request-status.rejected'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending  => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
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
