<?php

namespace Cesa\WhatsAppAuth\Support;

class PhoneNumber
{
    /**
     * Strip every non-digit character from the given phone number.
     */
    public static function digits(string $phone): string
    {
        return (string) preg_replace('/\D+/', '', $phone);
    }

    /**
     * Return the national significant number (without leading zero or country code).
     */
    public static function national(string $phone, string $countryCode = '62'): string
    {
        $digits = static::digits($phone);
        $countryCode = static::digits($countryCode);

        if ($countryCode !== '' && str_starts_with($digits, $countryCode)) {
            return ltrim(substr($digits, strlen($countryCode)), '0');
        }

        return ltrim($digits, '0');
    }

    /**
     * Build the list of digit-only candidates that may match stored phone numbers.
     *
     * @return array<int, string>
     */
    public static function candidates(string $phone, string $countryCode = '62'): array
    {
        $national = static::national($phone, $countryCode);

        if ($national === '') {
            return [];
        }

        $countryCode = static::digits($countryCode);

        return array_values(array_unique(array_filter([
            $national,
            '0'.$national,
            $countryCode.$national,
        ])));
    }

    /**
     * Format the number for delivery via WhatsApp (local format with leading zero).
     */
    public static function forDelivery(string $phone, string $countryCode = '62'): string
    {
        $national = static::national($phone, $countryCode);

        return $national === '' ? '' : '0'.$national;
    }
}
