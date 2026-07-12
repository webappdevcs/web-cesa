<?php

namespace Cesa\FormTransfer\Jobs;

use App\Services\WhatsAppGateway;
use App\Support\SendsWhatsAppViaGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWhatsAppNotification implements ShouldQueue
{
    use Queueable;
    use SendsWhatsAppViaGateway;

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
    ) {
        $queue = config('form-transfer.notifications.whatsapp.queue')
            ?? config('form-transfer.notifications.queue')
            ?? 'whatsapp';

        $this->onQueue($queue);

        if ($connection = config('form-transfer.notifications.whatsapp.connection')) {
            $this->onConnection($connection);
        }

        $this->tries = (int) (config('form-transfer.notifications.whatsapp.tries') ?? 3);
        $this->timeout = (int) (config('form-transfer.notifications.whatsapp.timeout') ?? 10);
        $this->backoff = $this->resolveBackoff();
    }

    public function handle(WhatsAppGateway $gateway): void
    {
        $this->sendViaWhatsAppGateway($gateway, 'Failed to send WhatsApp notification for transfer approval.');
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
            'form-transfer',
            'whatsapp',
            'transfer-request',
        ];
    }

    /**
     * Resolve the backoff configuration.
     *
     * @return array<int, int>
     */
    protected function resolveBackoff(): array
    {
        $backoff = config('form-transfer.notifications.whatsapp.backoff');

        if (is_array($backoff) && ! empty($backoff)) {
            return array_map(static fn ($interval): int => (int) $interval, $backoff);
        }

        return [10, 30, 60];
    }
}
