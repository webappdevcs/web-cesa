<?php

namespace Cesa\FormTransfer\Enums;

enum TransferRequestSubmissionStatus: string
{
    case BARU = 'baru';
    case REVISI = 'revisi';

    public function getLabel(): string
    {
        return match ($this) {
            self::BARU   => __('form-transfer::app.submission_statuses.baru'),
            self::REVISI => __('form-transfer::app.submission_statuses.revisi'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::BARU   => 'primary',
            self::REVISI => 'warning',
        };
    }

    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->getLabel()])
            ->all();
    }
}
