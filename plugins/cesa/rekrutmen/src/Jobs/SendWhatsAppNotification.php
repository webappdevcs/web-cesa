<?php

namespace Cesa\Rekrutmen\Jobs;

use App\Services\WhatsAppGateway;
use App\Support\SendsWhatsAppViaGateway;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWhatsAppNotification implements ShouldQueue
{
    use Queueable;
    use SendsWhatsAppViaGateway;

    public int $tries;

    protected int $timeout;

    /**
     * @var array<int, int>
     */
    protected array $backoff;

    public function __construct(
        protected string $phone,
        protected string $message,
    ) {
        $queue = config('rekrutmen.notifications.whatsapp.queue')
            ?? config('rekrutmen.notifications.queue')
            ?? 'whatsapp';

        $this->onQueue($queue);

        if ($connection = config('rekrutmen.notifications.whatsapp.connection')) {
            $this->onConnection($connection);
        }

        $this->tries = (int) (config('rekrutmen.notifications.whatsapp.tries') ?? 3);
        $this->timeout = (int) (config('rekrutmen.notifications.whatsapp.timeout') ?? 10);
        $this->backoff = $this->resolveBackoff();
    }

    public function handle(WhatsAppGateway $gateway): void
    {
        $this->sendViaWhatsAppGateway($gateway, 'Failed to send WhatsApp notification for recruitment approval.');
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return $this->backoff;
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'rekrutmen',
            'whatsapp',
            'request-man-power-approval',
        ];
    }

    /**
     * @return array<int, int>
     */
    protected function resolveBackoff(): array
    {
        $backoff = config('rekrutmen.notifications.whatsapp.backoff');

        if (is_array($backoff) && ! empty($backoff)) {
            return array_map(static fn ($interval): int => (int) $interval, $backoff);
        }

        return [10, 30, 60];
    }
}
