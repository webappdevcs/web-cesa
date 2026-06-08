<?php

namespace Cesa\WhatsAppAuth\Services\Gateways;

use Cesa\WhatsAppAuth\Contracts\WhatsAppGateway;
use Cesa\WhatsAppAuth\Exceptions\WhatsAppDeliveryException;
use Cesa\WhatsAppAuth\Support\PhoneNumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FonnteGateway implements WhatsAppGateway
{
    public function __construct(
        protected string $endpoint,
        protected ?string $token,
        protected string $countryCode = '62',
        protected int $timeout = 10,
    ) {}

    public function send(string $phone, string $message): void
    {
        if (blank($this->token)) {
            throw new WhatsAppDeliveryException('Fonnte API token is not configured.');
        }

        $target = PhoneNumber::forDelivery($phone, $this->countryCode);

        if ($target === '') {
            throw new WhatsAppDeliveryException('Invalid WhatsApp phone number.');
        }

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->withHeaders(['Authorization' => $this->token])
                ->asMultipart()
                ->post($this->endpoint, [
                    'target'      => $target,
                    'message'     => $message,
                    'countryCode' => $this->countryCode,
                ]);

            $response->throw();
        } catch (Throwable $exception) {
            Log::error('Failed to deliver WhatsApp OTP via Fonnte.', [
                'phone' => $target,
                'error' => $exception->getMessage(),
            ]);

            throw new WhatsAppDeliveryException('Failed to deliver WhatsApp OTP.', previous: $exception);
        }

        if ($this->resolveStatus($response->json()) === false) {
            $reason = $this->resolveReason($response->json());

            Log::warning('Fonnte rejected the WhatsApp OTP request.', [
                'phone'  => $target,
                'reason' => $reason,
            ]);

            throw new WhatsAppDeliveryException($reason);
        }
    }

    protected function resolveStatus(mixed $payload): ?bool
    {
        if (! is_array($payload) || ! array_key_exists('status', $normalized = array_change_key_case($payload))) {
            return null;
        }

        return filter_var($normalized['status'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    protected function resolveReason(mixed $payload): string
    {
        if (is_array($payload)) {
            $normalized = array_change_key_case($payload);

            foreach (['reason', 'detail', 'message'] as $key) {
                if (filled($normalized[$key] ?? null)) {
                    return (string) $normalized[$key];
                }
            }
        }

        return 'Fonnte rejected the request.';
    }
}
