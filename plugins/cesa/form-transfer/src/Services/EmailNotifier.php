<?php

namespace Cesa\FormTransfer\Services;

use Cesa\FormTransfer\Contracts\NotificationChannel;
use Illuminate\Support\Facades\Notification;

/**
 * Email notification channel implementation.
 */
class EmailNotifier implements NotificationChannel
{
    /**
     * Send an email notification.
     *
     * @param  string  $recipient  Email address
     * @param  string  $content  Email content (not used directly, expects notification object in context)
     * @param  array<string, mixed>  $context  Must contain 'notification' key with notification object
     */
    public function send(string $recipient, string $content, array $context = []): bool
    {
        if (! $this->shouldSend()) {
            return false;
        }

        if (! $this->validateRecipient($recipient)) {
            logger()->warning('Invalid email recipient', ['recipient' => $recipient]);

            return false;
        }

        if (! isset($context['notification'])) {
            logger()->error('Email notification object not provided in context');

            return false;
        }

        try {
            $notification = $context['notification'];
            $delaySeconds = app(MailThrottleService::class)->getDispatchDelaySeconds();

            if ($delaySeconds > 0 && is_object($notification) && method_exists($notification, 'delay')) {
                $notification->delay(now()->addSeconds($delaySeconds));
            }

            Notification::route('mail', $recipient)
                ->notify($notification);

            logger()->info('Email notification queued', [
                'recipient' => $recipient,
                'channel'   => 'email',
            ]);

            return true;
        } catch (\Exception $e) {
            logger()->error('Failed to send email notification', [
                'recipient' => $recipient,
                'error'     => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Validate email address format.
     */
    public function validateRecipient(string $recipient): bool
    {
        return filter_var($recipient, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Check if email notifications are enabled.
     *
     * @param  array<string, mixed>  $config  Configuration array
     */
    public function shouldSend(array $config = []): bool
    {
        return config('form-transfer.notifications.mail.enabled', true);
    }

    /**
     * Get the channel name.
     */
    public function getChannelName(): string
    {
        return 'email';
    }
}
