<?php

namespace Cesa\ExitClearance\Notifications;

use Cesa\ExitClearance\Models\Approver;
use Cesa\ExitClearance\Models\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array{label: string, value: string|null, type?: string}>  $summary
     * @param  array<int, array{approver_id: int, name: string|null, email: string|null, title: string|null, status: string, notes: string|null, approved_at: string|null}>  $approvals
     */
    public function __construct(
        protected Request $request,
        protected Approver $approver,
        protected array $summary,
        protected array $approvals,
        protected string $actionUrl,
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
        $subject = trim(sprintf('%s Approval Exit Clearance %s', $prefix, $this->request->form_uid ?? ''));

        return (new MailMessage)
            ->subject($subject)
            ->view('exit-clearance::mail.approval-request', [
                'request'     => $this->request,
                'approver'    => $this->approver,
                'summary'     => $this->summary,
                'approvals'   => $this->approvals,
                'actionUrl'   => $this->actionUrl,
                'progressUrl' => $this->progressUrl,
            ]);
    }
}
