<?php

namespace Cesa\FormTransfer\Contracts;

/**
 * Interface for notification delivery channels.
 */
interface NotificationChannel
{
    /**
     * Send a notification through this channel.
     *
     * @param  string  $recipient  Recipient identifier (email, phone, etc.)
     * @param  string  $content  Notification content
     * @param  array<string, mixed>  $context  Additional context data
     * @return bool Success status
     */
    public function send(string $recipient, string $content, array $context = []): bool;

    /**
     * Validate if the recipient is valid for this channel.
     *
     * @param  string  $recipient  Recipient identifier to validate
     */
    public function validateRecipient(string $recipient): bool;

    /**
     * Check if this channel should send notifications.
     *
     * @param  array<string, mixed>  $config  Configuration array
     */
    public function shouldSend(array $config = []): bool;

    /**
     * Get the channel name.
     */
    public function getChannelName(): string;
}
