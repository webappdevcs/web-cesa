<?php

namespace Cesa\FormTransfer\Enums;

/**
 * Enum representing the status of account validation.
 */
enum AccountValidationStatus: string
{
    case SUCCESS = 'success';
    case NOT_FOUND = 'not_found';
    case FAILED = 'failed';
    case RATE_LIMITED = 'rate_limited';

    /**
     * Get the label for the status.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::SUCCESS      => __('form-transfer::public.form.account_validation.success'),
            self::NOT_FOUND    => __('form-transfer::public.form.account_validation.not_found'),
            self::FAILED       => __('form-transfer::public.form.account_validation.failed'),
            self::RATE_LIMITED => __('form-transfer::public.form.account_validation.rate_limited'),
        };
    }

    /**
     * Check if the validation was successful.
     */
    public function isSuccessful(): bool
    {
        return $this === self::SUCCESS;
    }

    /**
     * Check if the validation can be retried.
     */
    public function canRetry(): bool
    {
        return in_array($this, [self::FAILED, self::RATE_LIMITED], true);
    }

    /**
     * Get all values as an array.
     *
     * @return array<string, string>
     */
    public static function getOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->getLabel()])
            ->all();
    }
}
