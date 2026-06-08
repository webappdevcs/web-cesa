<?php

namespace Cesa\WhatsAppAuth\Contracts;

use Cesa\WhatsAppAuth\Exceptions\WhatsAppDeliveryException;

interface WhatsAppGateway
{
    /**
     * Send a plain text WhatsApp message to the given phone number.
     *
     * @throws WhatsAppDeliveryException When the message cannot be delivered.
     */
    public function send(string $phone, string $message): void;
}
