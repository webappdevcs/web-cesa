<?php

namespace App\Support;

class WhatsAppGatewayConfiguration
{
    public static function isConfigured(): bool
    {
        $gatewayHubEndpoint = config('services.whatsapp_gateway.gateway_hub.endpoint');
        $gatewayHubToken = config('services.whatsapp_gateway.gateway_hub.token');
        $wahaBaseUrl = config('services.whatsapp_gateway.waha.base_url');
        $fonnteToken = config('services.whatsapp_gateway.fonnte.token');

        return (
            is_string($gatewayHubEndpoint)
            && trim($gatewayHubEndpoint) !== ''
            && is_string($gatewayHubToken)
            && trim($gatewayHubToken) !== ''
        )
            || (is_string($wahaBaseUrl) && trim($wahaBaseUrl) !== '')
            || (is_string($fonnteToken) && trim($fonnteToken) !== '');
    }
}
