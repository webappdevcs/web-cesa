<?php

namespace Cesa\WhatsAppAuth\Services\Gateways;

use Cesa\WhatsAppAuth\Contracts\WhatsAppGateway;
use Illuminate\Support\Facades\Log;

class LogGateway implements WhatsAppGateway
{
    public function send(string $phone, string $message): void
    {
        Log::info('WhatsApp OTP (log gateway).', [
            'phone'   => $phone,
            'message' => $message,
        ]);
    }
}
