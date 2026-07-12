<?php

namespace App\Support;

class WhatsAppGatewayConfiguration
{
    public static function isConfigured(): bool
    {
        $wahaBaseUrl = config('services.whatsapp_gateway.waha.base_url');
        $fonnteToken = config('services.whatsapp_gateway.fonnte.token');

        return (is_string($wahaBaseUrl) && trim($wahaBaseUrl) !== '')
            || (is_string($fonnteToken) && trim($fonnteToken) !== '');
    }
}
