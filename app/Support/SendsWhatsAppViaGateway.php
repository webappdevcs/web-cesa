<?php

namespace App\Support;

use App\Services\WhatsAppGateway;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

trait SendsWhatsAppViaGateway
{
    protected function sendViaWhatsAppGateway(WhatsAppGateway $gateway, string $logContext): void
    {
        try {
            if (! $gateway->send($this->phone, $this->message)) {
                throw new RuntimeException('Failed to send WhatsApp notification.');
            }
        } catch (Throwable $exception) {
            Log::error($logContext, [
                'phone' => $this->phone,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
