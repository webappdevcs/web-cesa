<?php

namespace Cesa\FormTransfer\Notifications;

use Cesa\FormTransfer\Models\TransferRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ApprovalRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected TransferRequest $transferRequest,
        protected array $summary,
        protected string $actionUrl,
        protected array $mailContent,
    ) {
        $this->onQueue(config('form-transfer.notifications.queue', 'default'));
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->mailContent['subject'] ?? '');

        if (! empty($this->mailContent['html'])) {
            return $mail->view('form-transfer::mail.custom-template', [
                'content' => new HtmlString($this->mailContent['html']),
            ]);
        }

        if (! empty($this->mailContent['greeting'])) {
            $mail->greeting($this->mailContent['greeting']);
        }

        foreach ($this->mailContent['lines'] ?? [] as $line) {
            $mail->line($line);
        }

        if (! empty($this->mailContent['actionText'])) {
            $mail->action($this->mailContent['actionText'], $this->actionUrl);
        }

        return $mail;
    }
}
