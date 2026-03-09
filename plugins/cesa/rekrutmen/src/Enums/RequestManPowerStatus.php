<?php

namespace Cesa\Rekrutmen\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RequestManPowerStatus: string implements HasColor, HasLabel
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING  => __('rekrutmen::app.enums.request_man_power_status.pending'),
            self::APPROVED => __('rekrutmen::app.enums.request_man_power_status.approved'),
            self::REJECTED => __('rekrutmen::app.enums.request_man_power_status.rejected'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING  => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
