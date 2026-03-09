<?php

namespace Cesa\FormTransfer\Enums;

enum TransferRequestApprovalStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING  => __('form-transfer::app.statuses.approval.pending'),
            self::APPROVED => __('form-transfer::app.statuses.approval.approved'),
            self::REJECTED => __('form-transfer::app.statuses.approval.rejected'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING  => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->getLabel()])
            ->all();
    }
}
