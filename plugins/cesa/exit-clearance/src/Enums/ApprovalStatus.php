<?php

namespace Cesa\ExitClearance\Enums;

enum ApprovalStatus: string
{
    case PENDING = 'pending';
    case WAITING = 'waiting';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING  => 'Pending',
            self::WAITING  => 'Waiting',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING  => 'gray',
            self::WAITING  => 'gray',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
