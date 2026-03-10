<?php

namespace Cesa\ExitClearance\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWhatsAppNotification implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries;

    /**
     * The timeout in seconds for the WhatsApp HTTP request.
     */
    protected int $timeout;

    /**
     * The backoff intervals in seconds between retries.
     *
     * @var array<int, int>
     */
    protected array $backoff;

    public function __construct(
        protected string $phone,
        protected string $message,
        protected string $endpoint,
        protected string $apiKey,
        protected string $sender,
        ?int $timeout = null,
    ) {
        $queue = config('exit-clearance.notifications.whatsapp.queue')
            ?? config('exit-clearance.notifications.queue')
            ?? 'default';

        $this->onQueue($queue);

        if ($connection = config('exit-clearance.notifications.whatsapp.connection')) {
            $this->onConnection($connection);
        }

        $this->tries = (int) (config('exit-clearance.notifications.whatsapp.tries') ?? 3);
        $this->timeout = $timeout ?? (int) (config('exit-clearance.notifications.whatsapp.timeout') ?? 10);
        $this->backoff = $this->resolveBackoff();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $provider = strtolower(trim((string) config('exit-clearance.notifications.whatsapp.provider', 'generic')));

        try {
            if ($provider === 'fonnte') {
                $countryCode = (string) config('exit-clearance.notifications.whatsapp.country_code', '62');

                $target = ltrim($this->phone, '+');

                if ($countryCode !== '' && str_starts_with($target, $countryCode)) {
                    $target = '0'.substr($target, strlen($countryCode));
                }

                $response = Http::timeout($this->timeout)
                    ->acceptJson()
                    ->withHeaders([
                        'Authorization' => $this->apiKey,
                    ])
                    ->asMultipart()
                    ->post($this->endpoint, [
                        'target'      => $target,
                        'message'     => $this->message,
                        'countryCode' => $countryCode,
                    ]);

                $response->throw();

                $payload = $response->json();

                if (is_array($payload) && array_key_exists('status', $payload) && $payload['status'] === false) {
                    $reason = $payload['reason'] ?? $payload['detail'] ?? 'Fonnte rejected the request.';
                    throw new \RuntimeException((string) $reason);
                }

                return;
            }

            Http::timeout($this->timeout)
                ->acceptJson()
                ->get($this->endpoint, [
                    'apikey'   => $this->apiKey,
                    'sender'   => $this->sender,
                    'receiver' => $this->phone,
                    'message'  => $this->message,
                ])
                ->throw();
        } catch (Throwable $exception) {
            Log::error('Failed to send WhatsApp notification for exit clearance.', [
                'provider' => $provider,
                'phone'    => $this->phone,
                'error'    => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * Determine the backoff intervals for the job retry attempts.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return $this->backoff;
    }

    /**
     * Define tags for queue monitoring systems like Horizon.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'exit-clearance',
            'whatsapp',
        ];
    }

    /**
     * Resolve the backoff configuration.
     *
     * @return array<int, int>
     */
    protected function resolveBackoff(): array
    {
        $backoff = config('exit-clearance.notifications.whatsapp.backoff');

        if (is_array($backoff) && ! empty($backoff)) {
            return array_map(static fn ($interval): int => (int) $interval, $backoff);
        }

        return [10, 30, 60];
    }
}
