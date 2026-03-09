<?php

namespace Cesa\Rekrutmen\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusKebutuhan: string implements HasColor, HasLabel
{
    case NEW_HIRING = 'New Hiring';
    case REPLACEMENT = 'Replacement';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NEW_HIRING  => __('rekrutmen::app.enums.status_kebutuhan.new_hiring'),
            self::REPLACEMENT => __('rekrutmen::app.enums.status_kebutuhan.replacement'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NEW_HIRING  => 'success',
            self::REPLACEMENT => 'warning',
        };
    }
}
