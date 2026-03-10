<?php

namespace Cesa\ExitClearance\Notifications;

use Cesa\ExitClearance\Models\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array{label: string, value: string|null, type?: string}>  $summary
     * @param  array<int, array{approver_id: int, name: string|null, email: string|null, title: string|null, status: string, notes: string|null, approved_at: string|null}>  $approvals
     */
    public function __construct(
        protected Request $request,
        protected string $statusLabel,
        protected array $summary,
        protected array $approvals,
        protected string $progressUrl,
    ) {
        $this->onQueue(config('exit-clearance.notifications.queue', 'notifications'));
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $prefix = config('exit-clearance.notifications.mail.subject_prefix', '[Exit Clearance]');
        $subject = trim(sprintf('%s Status Exit Clearance %s', $prefix, $this->request->form_uid ?? ''));

        return (new MailMessage)
            ->subject($subject)
            ->view('exit-clearance::mail.request-status', [
                'request'     => $this->request,
                'statusLabel' => $this->statusLabel,
                'summary'     => $this->summary,
                'approvals'   => $this->approvals,
                'progressUrl' => $this->progressUrl,
            ]);
    }
}
