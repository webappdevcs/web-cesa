<?php

namespace Cesa\FormTransfer\Enums;

enum ApprovalStatus: string
{
    case PENDING = 'pending';
    case WAITING = 'waiting';
    case APPROVED = 'approved';
    case REVISI = 'revisi';
    case DITOLAK = 'ditolak';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING  => __('form-transfer::app.approval_statuses.pending'),
            self::WAITING  => __('form-transfer::app.approval_statuses.waiting'),
            self::APPROVED => __('form-transfer::app.approval_statuses.approved'),
            self::REVISI   => __('form-transfer::app.approval_statuses.revisi'),
            self::DITOLAK  => __('form-transfer::app.approval_statuses.ditolak'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING  => 'gray',
            self::WAITING  => 'gray',
            self::APPROVED => 'success',
            self::REVISI   => 'warning',
            self::DITOLAK  => 'danger',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->getLabel()])
            ->all();
    }
}
