<?php

namespace Cesa\FormTransfer\Services;

use Cesa\FormTransfer\Contracts\NotificationChannel;
use Cesa\FormTransfer\Jobs\SendWhatsAppNotification;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp notification channel implementation.
 */
class WhatsAppNotifier implements NotificationChannel
{
    /**
     * Send a WhatsApp notification.
     *
     * @param  string  $recipient  Phone number
     * @param  string  $content  Message content
     * @param  array<string, mixed>  $context  Additional context (optional)
     */
    public function send(string $recipient, string $content, array $context = []): bool
    {
        if (! $this->shouldSend()) {
            return false;
        }

        $formattedPhone = $this->formatPhone($recipient);

        if (! $this->validateRecipient($formattedPhone)) {
            logger()->warning('Invalid WhatsApp recipient', ['recipient' => $recipient]);

            return false;
        }

        try {
            $endpoint = config('form-transfer.notifications.whatsapp.endpoint');
            $apiKey = config('form-transfer.notifications.whatsapp.api_key');
            $sender = config('form-transfer.notifications.whatsapp.sender');
            $timeout = config('form-transfer.notifications.whatsapp.timeout', 10);
            $delaySeconds = app(WhatsAppThrottleService::class)->getDispatchDelaySeconds();

            $pendingDispatch = SendWhatsAppNotification::dispatch(
                $formattedPhone,
                $content,
                $endpoint,
                $apiKey,
                (string) ($sender ?? ''),
                $timeout
            );

            if ($delaySeconds > 0) {
                $pendingDispatch->delay($delaySeconds);
            }

            logger()->info('WhatsApp notification queued', [
                'recipient' => $formattedPhone,
                'channel'   => 'whatsapp',
            ]);

            return true;
        } catch (\Exception $e) {
            logger()->error('Failed to queue WhatsApp notification', [
                'recipient' => $formattedPhone,
                'error'     => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Validate phone number format.
     */
    public function validateRecipient(string $recipient): bool
    {
        if (empty($recipient)) {
            return false;
        }

        // Basic validation: must contain only digits, +, -, (), and spaces
        return preg_match('/^[\d\s\-\+\(\)]+$/', $recipient) === 1;
    }

    /**
     * Check if WhatsApp notifications are enabled.
     *
     * @param  array<string, mixed>  $config  Configuration array
     */
    public function shouldSend(array $config = []): bool
    {
        $enabled = config('form-transfer.notifications.whatsapp.enabled', false);
        $provider = strtolower(trim((string) config('form-transfer.notifications.whatsapp.provider', 'generic')));
        $endpoint = config('form-transfer.notifications.whatsapp.endpoint');
        $apiKey = config('form-transfer.notifications.whatsapp.api_key');
        $sender = config('form-transfer.notifications.whatsapp.sender');

        if (! $enabled) {
            return false;
        }

        if (empty($endpoint)) {
            Log::warning('WhatsApp notifications enabled but endpoint not configured');

            return false;
        }

        if (empty($apiKey)) {
            Log::warning('WhatsApp notifications enabled but API key not configured');

            return false;
        }

        if ($provider !== 'fonnte' && empty($sender)) {
            Log::warning('WhatsApp notifications enabled but sender not configured');

            return false;
        }

        return true;
    }

    /**
     * Format phone number for WhatsApp API.
     *
     * Removes non-digit characters except leading +.
     */
    public function formatPhone(string $phone): string
    {
        $trimmed = trim($phone);

        // Keep leading + if present
        $hasPlus = str_starts_with($trimmed, '+');

        // Remove all non-digit characters
        $digitsOnly = preg_replace('/[^\d]/', '', $trimmed);

        return $hasPlus ? '+'.$digitsOnly : $digitsOnly;
    }

    /**
     * Get the channel name.
     */
    public function getChannelName(): string
    {
        return 'whatsapp';
    }
}
