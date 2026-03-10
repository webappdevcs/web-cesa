<?php

namespace Cesa\FormTransfer\Services;

use Cesa\FormTransfer\Enums\TransferRequestApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestRealizationStatus;
use Cesa\FormTransfer\Enums\TransferRequestSubmissionStatus;
use Cesa\FormTransfer\Jobs\SendWhatsAppNotification;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Notifications\ApprovalRequestNotification;
use Cesa\FormTransfer\Notifications\RequestStatusNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use Throwable;

class TransferApprovalNotificationService
{
    /**
     * Common placeholders available for all notification templates.
     *
     * @var array<int, string>
     */
    public const COMMON_PLACEHOLDERS = [
        'title',
        'uid',
        'email',
        'requester_name',
        'division',
        'account_number',
        'account_name',
        'bank',
        'transfer_amount',
        'purpose',
        'reference_note',
        'invoice',
        'account_attachment',
        'status',
        'summary_table',
        'action_text',
        'current_year',
    ];

    /**
     * Approver specific placeholders.
     *
     * @var array<int, string>
     */
    public const APPROVER_PLACEHOLDERS = [
        'approver_name',
        'approver_email',
        'approver_phone',
        'approver_title',
        'approver_status',
        'approver_list',
        'action_url',
        'progress_url',
        'approvals_table',
        'action_button',
    ];

    /**
     * Requester specific placeholders.
     *
     * @var array<int, string>
     */
    public const REQUESTER_PLACEHOLDERS = [
        'progress_url',
        'status_label',
        'action_url',
        'approvals_table',
        'action_button',
    ];

    /**
     * Get the default plain-text template for approver email content.
     */
    public static function getDefaultApproverMailTemplate(): string
    {
        return implode("\n", [
            'Mohon tinjau pengajuan transfer berikut:',
            'UID: {{ uid }}',
            'Nama Pemohon: {{ requester_name }}',
            'Divisi: {{ division }}',
            'Jumlah Transfer: Rp {{ transfer_amount }}',
            'Keperluan: {{ purpose }}',
            'Status saat ini: {{ status }}',
            'Daftar approver:',
            '{{ approver_list }}',
            'Terima kasih.',
        ]);
    }

    public static function getDefaultApproverMailSubject(): string
    {
        $prefix = config('form-transfer.notifications.mail.subject_prefix', '[Transfer Request]');

        return "{$prefix} Approval Required - {{ title }}";
    }

    public static function getDefaultApproverMailGreeting(): string
    {
        return 'Halo {{ approver_name }},';
    }

    public static function getDefaultApproverMailActionText(): string
    {
        return 'Buka Halaman Approval';
    }

    /**
     * Get the default plain-text template for requester email content.
     */
    public static function getDefaultRequesterMailTemplate(): string
    {
        return implode("\n", [
            'Status pengajuan transfer Anda diperbarui menjadi: {{ status_label }}',
            'UID: {{ uid }}',
            'Divisi: {{ division }}',
            'Jumlah Transfer: Rp {{ transfer_amount }}',
        ]);
    }

    public static function getDefaultRequesterMailSubject(): string
    {
        $prefix = config('form-transfer.notifications.mail.subject_prefix', '[Transfer Request]');

        return "{$prefix} {{ title }} - {{ status_label }}";
    }

    public static function getDefaultRequesterMailGreeting(): string
    {
        return 'Halo {{ requester_name }},';
    }

    public static function getDefaultRequesterMailActionText(): string
    {
        return 'Lihat Progres Approval';
    }

    /**
     * Retrieve the list of placeholders available for approver notifications.
     *
     * @return array<int, string>
     */
    public static function getApproverPlaceholders(): array
    {
        return array_values(array_unique(array_merge(self::COMMON_PLACEHOLDERS, self::APPROVER_PLACEHOLDERS)));
    }

    /**
     * Retrieve the list of placeholders available for requester notifications.
     *
     * @return array<int, string>
     */
    public static function getRequesterPlaceholders(): array
    {
        return array_values(array_unique(array_merge(self::COMMON_PLACEHOLDERS, self::REQUESTER_PLACEHOLDERS)));
    }

    public function notifyApprover(TransferRequest $request, array $approval, array $approvals): void
    {
        if (! ($approval['email'] ?? null)) {
            return;
        }

        $summary = $this->getRequestSummary($request);
        $actionUrl = $this->buildApprovalUrl($approval);

        if (config('form-transfer.notifications.mail.enabled', true)) {
            $mailContent = $this->buildApproverMailContent($request, $approval, $approvals, $summary, $actionUrl);
            $notification = new ApprovalRequestNotification($request, $summary, $actionUrl, $mailContent);
            $delaySeconds = app(MailThrottleService::class)->getDispatchDelaySeconds();

            if ($delaySeconds > 0) {
                $notification->delay(now()->addSeconds($delaySeconds));
            }

            Notification::route('mail', $approval['email'])
                ->notify($notification);
        }

        $this->sendWhatsApp(
            $approval['phone'] ?? null,
            $this->buildApproverWhatsappMessage($request, $summary, $approval, $approvals, $actionUrl)
        );
    }

    public function notifyRequester(TransferRequest $request, string $statusLabel): void
    {
        if (! $request->email) {
            return;
        }

        if (! config('form-transfer.notifications.mail.enabled', true)) {
            return;
        }

        $summary = $this->getRequestSummary($request);
        $progressUrl = $this->buildProgressUrl($request);
        $mailContent = $this->buildRequesterMailContent($request, $statusLabel, $summary, $progressUrl);

        $notification = new RequestStatusNotification($request, $statusLabel, $progressUrl, $summary, $mailContent);
        $delaySeconds = app(MailThrottleService::class)->getDispatchDelaySeconds();

        if ($delaySeconds > 0) {
            $notification->delay(now()->addSeconds($delaySeconds));
        }

        Notification::route('mail', $request->email)
            ->notify($notification);
    }

    public function notifyRequesterNow(TransferRequest $request, string $statusLabel): void
    {
        if (! $request->email) {
            return;
        }

        if (! config('form-transfer.notifications.mail.enabled', true)) {
            return;
        }

        $summary = $this->getRequestSummary($request);
        $progressUrl = $this->buildProgressUrl($request);
        $mailContent = $this->buildRequesterMailContent($request, $statusLabel, $summary, $progressUrl);

        Notification::route('mail', $request->email)
            ->notifyNow(new RequestStatusNotification($request, $statusLabel, $progressUrl, $summary, $mailContent));
    }

    public function notifyRequesterWithCurrentStatus(TransferRequest $request): void
    {
        $this->notifyRequester($request, $this->resolveStatusLabel($request));
    }

    public function notifyRequesterWithCurrentStatusNow(TransferRequest $request): void
    {
        $this->notifyRequesterNow($request, $this->resolveStatusLabel($request));
    }

    public function notifyRequesterForFinalStatus(TransferRequest $request): void
    {
        $approvalStatus = $request->approval_status;

        if (! $approvalStatus instanceof TransferRequestApprovalStatus) {
            $approvalStatus = TransferRequestApprovalStatus::tryFrom((string) $approvalStatus);
        }

        if (! $approvalStatus) {
            return;
        }

        // Skip notifications while approval is still pending to avoid duplicates
        if ($approvalStatus === TransferRequestApprovalStatus::PENDING) {
            return;
        }

        $realizationStatus = $request->realization_status instanceof TransferRequestRealizationStatus
            ? $request->realization_status
            : TransferRequestRealizationStatus::tryFrom((string) $request->realization_status);

        if ($realizationStatus === TransferRequestRealizationStatus::DONE) {
            return;
        }

        $this->notifyRequester($request, $this->resolveStatusLabel($request));
    }

    protected function buildApprovalUrl(array $approval): string
    {
        return route('form-transfer.public.approval', [
            'task' => $approval['task_id'],
        ]);
    }

    protected function buildProgressUrl(TransferRequest $request): string
    {
        return route('form-transfer.public.progress', [
            'response' => $request->status_response_id,
        ]);
    }

    protected function buildApproverWhatsappMessage(TransferRequest $request, array $summary, array $approval, array $approvals, string $actionUrl): ?string
    {
        if (! ($approval['phone'] ?? null)) {
            return null;
        }

        $request->loadMissing('formTransfer');

        $form = $request->formTransfer;
        $progressUrl = $this->buildProgressUrl($request);
        $variables = $this->buildApproverTemplateVariables($summary, $approval, $approvals, $actionUrl, $progressUrl);
        $template = $this->renderTemplate($form?->approver_whatsapp_template, $variables);

        if ($template !== null) {
            return $template;
        }

        $lines = [
            '*📣 NOTIFIKASI APPROVAL - '.$summary['title'].'*',
            '',
            '*UID:* '.$summary['uid'],
            '*Email:* '.$summary['email'],
            '*Nama:* '.$summary['requester_name'],
            '*Divisi:* '.$summary['division'],
            '',
            '*Nomor Rekening:* '.$summary['account_number'],
            '*Nama Pemilik Rekening:* '.$summary['account_name'],
            '*Bank:* '.$summary['bank'],
            '*Jumlah Transfer:* Rp '.$summary['transfer_amount'],
            '*Keperluan:* '.$summary['purpose'],
            '*Reffnote:* '.$summary['reference_note'],
            '*Lampiran Invoice:* '.($summary['invoice'] ?? __('form-transfer::app.notifications.invoice_missing')),
            '*Status Transfer:* '.$summary['status'],
            '',
            '*Klik link berikut untuk Approve atau Reject:*',
            $actionUrl,
            '',
            '— *Bot by IT*',
        ];

        return implode("\n", $lines);
    }

    protected function buildApproverMailContent(TransferRequest $request, array $approval, array $approvals, array $summary, string $actionUrl): array
    {
        $request->loadMissing('formTransfer');

        $form = $request->formTransfer;
        $progressUrl = $this->buildProgressUrl($request);
        $baseVariables = $this->buildApproverTemplateVariables($summary, $approval, $approvals, $actionUrl, $progressUrl);

        $defaultSubject = $this->buildApproverMailSubject($request);
        $defaultGreeting = 'Halo '.($baseVariables['approver_name'] ?? 'Approver').',';
        $defaultActionText = 'Buka Halaman Approval';
        $defaultLines = [
            'Mohon tinjau pengajuan transfer berikut:',
            'UID: '.$summary['uid'],
            'Nama Pemohon: '.$summary['requester_name'],
            'Divisi: '.$summary['division'],
            'Jumlah Transfer: Rp '.$summary['transfer_amount'],
            'Keperluan: '.$summary['purpose'],
            'Status saat ini: '.$summary['status'],
            'Daftar approver:',
            $baseVariables['approver_list'] ?? '',
            'Terima kasih.',
        ];

        $actionText = $this->renderTemplate($form?->approver_mail_action_text, $baseVariables) ?? $defaultActionText;
        $variables = array_merge($baseVariables, [
            'action_text'   => $actionText,
            'action_button' => $this->buildActionButton($actionUrl, $actionText),
        ]);

        $subject = $this->renderTemplate($form?->approver_mail_subject, $variables) ?? $defaultSubject;
        $greeting = $this->renderTemplate($form?->approver_mail_greeting, $variables) ?? $defaultGreeting;
        $body = $this->prepareMailBody(
            $this->renderTemplate($form?->approver_mail_template, $variables),
            $defaultLines
        );

        return [
            'subject'    => $subject,
            'greeting'   => $greeting,
            'lines'      => $body['lines'],
            'html'       => $body['html'],
            'actionText' => $actionText,
        ];
    }

    protected function buildRequesterMailContent(TransferRequest $request, string $statusLabel, array $summary, string $progressUrl): array
    {
        $request->loadMissing('formTransfer');

        $form = $request->formTransfer;
        $baseVariables = $this->buildRequesterTemplateVariables($summary, $statusLabel, $progressUrl, $request->approvals ?? []);

        $defaultSubject = $this->buildRequesterMailSubject($request, $statusLabel);
        $defaultGreeting = 'Halo '.$summary['requester_name'].',';
        $defaultActionText = 'Lihat Progres Approval';
        $defaultLines = [
            'Status pengajuan transfer Anda diperbarui menjadi: '.$statusLabel,
            'UID: '.$summary['uid'],
            'Divisi: '.$summary['division'],
            'Jumlah Transfer: Rp '.$summary['transfer_amount'],
        ];

        $actionText = $this->renderTemplate($form?->requester_mail_action_text, $baseVariables) ?? $defaultActionText;
        $variables = array_merge($baseVariables, [
            'action_text'   => $actionText,
            'action_button' => $this->buildActionButton($progressUrl, $actionText),
        ]);

        $subject = $this->renderTemplate($form?->requester_mail_subject, $variables) ?? $defaultSubject;
        $greeting = $this->renderTemplate($form?->requester_mail_greeting, $variables) ?? $defaultGreeting;
        $body = $this->prepareMailBody(
            $this->renderTemplate($form?->requester_mail_template, $variables),
            $defaultLines
        );

        return [
            'subject'    => $subject,
            'greeting'   => $greeting,
            'lines'      => $body['lines'],
            'html'       => $body['html'],
            'actionText' => $actionText,
        ];
    }

    protected function buildApproverMailSubject(TransferRequest $request): string
    {
        $prefix = config('form-transfer.notifications.mail.subject_prefix', '[Transfer Request]');
        $formName = $request->formTransfer?->name ?? 'Form Transfer';

        return "{$prefix} Approval Required - {$formName}";
    }

    protected function buildRequesterMailSubject(TransferRequest $request, string $statusLabel): string
    {
        $prefix = config('form-transfer.notifications.mail.subject_prefix', '[Transfer Request]');
        $formName = $request->formTransfer?->name ?? 'Form Transfer';

        return sprintf('%s %s - %s', $prefix, $formName, $statusLabel);
    }

    public function getRequestSummary(TransferRequest $request): array
    {
        return $this->buildRequestSummary($request);
    }

    protected function buildRequestSummary(TransferRequest $request): array
    {
        $request->loadMissing(['formTransfer', 'division', 'bank']);
        $invoiceLinks = $this->buildAttachmentUrls($request, 'invoice_path');
        $accountAttachmentLinks = $this->buildAttachmentUrls($request, 'account_attachment_path');

        return [
            'title'                    => $request->formTransfer?->name ?? 'Form Transfer',
            'uid'                      => $request->uid,
            'email'                    => $request->email ?? '-',
            'requester_name'           => $request->requester_name ?? '-',
            'division'                 => $request->division_name ?? '-',
            'account_number'           => $request->account_number ?? '-',
            'account_name'             => $request->account_name ?? '-',
            'bank'                     => $request->bank_display_name ?? $request->bank?->code ?? '-',
            'transfer_amount'          => number_format((float) ($request->transfer_amount ?? 0), 0, ',', '.'),
            'purpose'                  => $request->purpose ?? '-',
            'reference_note'           => $request->reference_note ?? '-',
            'invoice'                  => $invoiceLinks[0] ?? null,
            'invoice_links'            => $invoiceLinks,
            'account_attachment'       => $accountAttachmentLinks[0] ?? null,
            'account_attachment_links' => $accountAttachmentLinks,
            'realization_notes'        => $request->realization_notes ?? '-',
            'status'                   => $this->resolveStatusLabel($request),
            'status_color'             => $this->resolveStatusColor($request),
        ];
    }

    protected function buildAttachmentUrl(TransferRequest $request, string $attribute): ?string
    {
        $urls = $this->buildAttachmentUrls($request, $attribute);

        return $urls[0] ?? null;
    }

    /**
     * @return array<int, string>
     */
    protected function buildAttachmentUrls(TransferRequest $request, string $attribute): array
    {
        $paths = TransferRequest::normalizeAttachmentPaths($request->{$attribute});

        if ($paths === []) {
            return [];
        }

        $attachmentType = match ($attribute) {
            'invoice_path'            => 'invoice',
            'account_attachment_path' => 'account-attachment',
            default                   => null,
        };

        if (! $attachmentType) {
            return [];
        }

        $urls = [];

        foreach ($paths as $index => $path) {
            $url = $this->buildAttachmentUrlFor($request, $attachmentType, $index);

            if ($url) {
                $urls[] = $url;
            }
        }

        return $urls;
    }

    protected function buildAttachmentUrlFor(TransferRequest $request, string $attachmentType, int $index): ?string
    {
        if (blank($request->status_response_id)) {
            return null;
        }

        try {
            return URL::temporarySignedRoute(
                'form-transfer.public.attachments.download',
                now()->addMinutes(60),
                [
                    'statusResponseId' => $request->status_response_id,
                    'attachment'       => $attachmentType,
                    'file'             => $index,
                ],
            );
        } catch (Throwable $exception) {
            Log::warning('Failed generating attachment url.', [
                'transfer_request_id' => $request->getKey(),
                'attachment_type'     => $attachmentType,
                'error'               => $exception->getMessage(),
            ]);

            return null;
        }
    }

    protected function resolveStatusLabel(TransferRequest $request): string
    {
        $submissionStatus = $request->submission_status instanceof TransferRequestSubmissionStatus
            ? $request->submission_status
            : TransferRequestSubmissionStatus::tryFrom((string) $request->submission_status);

        $approvalStatus = $request->approval_status instanceof TransferRequestApprovalStatus
            ? $request->approval_status
            : TransferRequestApprovalStatus::tryFrom((string) $request->approval_status);

        $realizationStatus = $request->realization_status instanceof TransferRequestRealizationStatus
            ? $request->realization_status
            : TransferRequestRealizationStatus::tryFrom((string) $request->realization_status);

        if ($realizationStatus === TransferRequestRealizationStatus::DONE) {
            return $realizationStatus->getLabel();
        }

        if ($realizationStatus === TransferRequestRealizationStatus::CANCELLED) {
            return $realizationStatus->getLabel();
        }

        if ($approvalStatus === TransferRequestApprovalStatus::REJECTED) {
            return $approvalStatus->getLabel();
        }

        if ($approvalStatus === TransferRequestApprovalStatus::APPROVED) {
            return $approvalStatus->getLabel();
        }

        if ($submissionStatus === TransferRequestSubmissionStatus::REVISI) {
            return $submissionStatus->getLabel();
        }

        return $submissionStatus?->getLabel() ?? __('form-transfer::app.statuses.unknown');
    }

    protected function resolveStatusColor(TransferRequest $request): string
    {
        $submissionStatus = $request->submission_status instanceof TransferRequestSubmissionStatus
            ? $request->submission_status
            : TransferRequestSubmissionStatus::tryFrom((string) $request->submission_status);

        $approvalStatus = $request->approval_status instanceof TransferRequestApprovalStatus
            ? $request->approval_status
            : TransferRequestApprovalStatus::tryFrom((string) $request->approval_status);

        $realizationStatus = $request->realization_status instanceof TransferRequestRealizationStatus
            ? $request->realization_status
            : TransferRequestRealizationStatus::tryFrom((string) $request->realization_status);

        if ($realizationStatus === TransferRequestRealizationStatus::DONE) {
            return $realizationStatus->getColor();
        }

        if ($realizationStatus === TransferRequestRealizationStatus::CANCELLED) {
            return $realizationStatus->getColor();
        }

        if ($approvalStatus === TransferRequestApprovalStatus::REJECTED) {
            return $approvalStatus->getColor();
        }

        if ($approvalStatus === TransferRequestApprovalStatus::APPROVED) {
            return $approvalStatus->getColor();
        }

        if ($submissionStatus === TransferRequestSubmissionStatus::REVISI) {
            return $submissionStatus->getColor();
        }

        return $submissionStatus?->getColor() ?? 'gray';
    }

    protected function prepareMailBody(?string $renderedTemplate, array $defaultLines): array
    {
        if ($renderedTemplate !== null && $this->looksLikeHtml($renderedTemplate)) {
            return [
                'html'  => $renderedTemplate,
                'lines' => [],
            ];
        }

        return [
            'html'  => null,
            'lines' => $this->resolveMailLines($renderedTemplate, $defaultLines),
        ];
    }

    protected function resolveMailLines(?string $rendered, array $defaultLines): array
    {
        if ($rendered === null) {
            return $defaultLines;
        }

        $lines = $this->splitTemplateLines($rendered);

        return $lines === [] ? $defaultLines : $lines;
    }

    protected function buildApproverTemplateVariables(array $summary, array $approval, array $approvals, string $actionUrl, string $progressUrl): array
    {
        $approverList = $this->buildApproverList($approvals);

        return array_merge(
            $this->buildCommonTemplateVariables($summary),
            [
                'approver_name'   => $approval['name'] ?? 'Approver',
                'approver_email'  => $approval['email'] ?? '',
                'approver_phone'  => $approval['phone'] ?? '',
                'approver_title'  => $approval['title'] ?? '',
                'approver_status' => isset($approval['status']) ? ucfirst((string) $approval['status']) : '',
                'approver_list'   => $approverList,
                'approvals_table' => $this->buildApprovalsTable($approvals),
                'action_url'      => $actionUrl,
                'progress_url'    => $progressUrl,
            ]
        );
    }

    protected function buildRequesterTemplateVariables(array $summary, string $statusLabel, string $progressUrl, array $approvals): array
    {
        return array_merge(
            $this->buildCommonTemplateVariables($summary),
            [
                'progress_url'    => $progressUrl,
                'status_label'    => $statusLabel,
                'approvals_table' => $this->buildApprovalsTable($approvals),
                'action_url'      => $progressUrl,
            ]
        );
    }

    protected function buildCommonTemplateVariables(array $summary): array
    {
        return [
            'title'              => $summary['title'],
            'uid'                => $summary['uid'],
            'email'              => $summary['email'],
            'requester_name'     => $summary['requester_name'],
            'division'           => $summary['division'],
            'account_number'     => $summary['account_number'],
            'account_name'       => $summary['account_name'],
            'bank'               => $summary['bank'],
            'transfer_amount'    => $summary['transfer_amount'],
            'purpose'            => $summary['purpose'],
            'reference_note'     => $summary['reference_note'],
            'invoice'            => $summary['invoice'] ?? __('form-transfer::app.notifications.invoice_missing'),
            'account_attachment' => $summary['account_attachment'] ?? __('form-transfer::app.notifications.account_attachment_missing'),
            'status'             => $summary['status'],
            'summary_table'      => $this->buildSummaryTable($summary),
            'action_text'        => '',
            'current_year'       => Carbon::now()->format('Y'),
        ];
    }

    protected function buildApproverList(array $approvals): string
    {
        return collect($approvals)
            ->map(function (array $approval): string {
                $status = $approval['status'] ?? '-';
                $name = $approval['name'] ?? '-';
                $title = $approval['title'] ?? '';

                return sprintf('- %s%s (%s)', $name, $title ? " - {$title}" : '', ucfirst((string) $status));
            })
            ->implode("\n");
    }

    protected function buildSummaryTable(array $summary): string
    {
        $rows = [
            __('form-transfer::app.fields.uid')                => $summary['uid'] ?? '—',
            __('form-transfer::app.fields.requester_name')     => $summary['requester_name'] ?? '—',
            __('form-transfer::app.fields.email')              => $summary['email'] ?? '—',
            __('form-transfer::app.fields.division')           => $summary['division'] ?? '—',
            __('form-transfer::app.fields.account_number')     => $summary['account_number'] ?? '—',
            __('form-transfer::app.fields.account_name')       => $summary['account_name'] ?? '—',
            __('form-transfer::app.fields.bank_name')          => $summary['bank'] ?? '—',
            __('form-transfer::app.fields.transfer_amount')    => 'Rp '.$summary['transfer_amount'],
            __('form-transfer::app.fields.purpose')            => $summary['purpose'] ?? '—',
            __('form-transfer::app.fields.reference_note')     => $summary['reference_note'] ?? '—',
            __('form-transfer::app.fields.status')             => $summary['status'] ?? '—',
            __('form-transfer::app.fields.invoice')            => $summary['invoice_links'] ?? ($summary['invoice'] ?? '—'),
            __('form-transfer::app.fields.account_attachment') => $summary['account_attachment_links'] ?? ($summary['account_attachment'] ?? '—'),
        ];

        return $this->buildKeyValueTable($rows);
    }

    protected function buildApprovalsTable(array $approvals): string
    {
        if ($approvals === []) {
            return '';
        }

        $headers = [
            __('form-transfer::app.fields.approver_name'),
            __('form-transfer::app.fields.approver_title'),
            __('form-transfer::app.fields.approver_status'),
            __('form-transfer::app.fields.approver_notes'),
            __('form-transfer::app.fields.approver_noted_at'),
        ];

        $rows = collect($approvals)->map(function (array $approval): array {
            $status = $approval['status'] ?? '';

            return [
                $approval['name'] ?? '-',
                $approval['title'] ?? '-',
                $status !== '' ? ucfirst((string) $status) : '-',
                $approval['notes'] ?? ($approval['note'] ?? ''),
                $this->formatTimestamp($approval['noted_at'] ?? null),
            ];
        })->all();

        return $this->buildTable($headers, $rows);
    }

    protected function buildKeyValueTable(array $rows): string
    {
        $body = collect($rows)
            ->map(function ($value, $label): string {
                return sprintf(
                    '<tr><td style="padding:8px 12px; border:1px solid #d1d5db; background-color:#f9fafb; font-weight:600;">%s</td><td style="padding:8px 12px; border:1px solid #d1d5db;">%s</td></tr>',
                    e((string) $label),
                    $this->normalizeTableValue($value)
                );
            })
            ->implode('');

        if ($body === '') {
            return '';
        }

        return sprintf(
            '<table style="width:100%%; border-collapse:collapse; margin:16px 0; border-radius:6px; overflow:hidden;"><tbody>%s</tbody></table>',
            $body
        );
    }

    protected function buildTable(array $headers, array $rows): string
    {
        if ($rows === []) {
            return '';
        }

        $headerHtml = collect($headers)
            ->map(fn ($header): string => sprintf(
                '<th style="padding:10px 12px; background-color:#2563eb; color:#ffffff; text-align:left; border:1px solid #1d4ed8;">%s</th>',
                e((string) $header)
            ))
            ->implode('');

        $bodyHtml = collect($rows)
            ->map(function (array $row): string {
                $cells = collect($row)
                    ->map(fn ($cell): string => sprintf(
                        '<td style="padding:8px 12px; border:1px solid #d1d5db; vertical-align:top;">%s</td>',
                        $this->normalizeTableValue($cell)
                    ))
                    ->implode('');

                return sprintf('<tr>%s</tr>', $cells);
            })
            ->implode('');

        return sprintf(
            '<table style="width:100%%; border-collapse:collapse; margin:16px 0; border-radius:6px; overflow:hidden;"><thead><tr>%s</tr></thead><tbody>%s</tbody></table>',
            $headerHtml,
            $bodyHtml
        );
    }

    protected function buildActionButton(?string $url, string $text): string
    {
        if (! $url || trim($text) === '') {
            return '';
        }

        $style = implode(' ', [
            'display:inline-block;',
            'padding:14px 24px;',
            'background-color:#2563eb;',
            'color:#ffffff;',
            'border-radius:12px;',
            'text-decoration:none;',
            'font-weight:600;',
        ]);

        return sprintf('<a href="%s" target="_blank" style="%s">%s</a>', e($url), $style, e($text));
    }

    protected function normalizeTableValue(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if ($value instanceof HtmlString) {
            return $value->toHtml();
        }

        if ($value instanceof \Stringable) {
            $value = (string) $value;
        }

        if (is_bool($value)) {
            $value = $value ? __('Yes') : __('No');
        }

        if (is_array($value)) {
            $value = implode(', ', array_map(fn ($item) => (string) $item, $value));
        }

        $stringValue = (string) $value;

        if (trim($stringValue) === '') {
            return '—';
        }

        return nl2br(e($stringValue));
    }

    protected function formatTimestamp(?string $timestamp): string
    {
        if (! $timestamp) {
            return '—';
        }

        try {
            return Carbon::parse($timestamp)
                ->timezone(config('app.timezone'))
                ->format('d M Y H:i');
        } catch (Throwable) {
            return (string) $timestamp;
        }
    }

    protected function looksLikeHtml(string $content): bool
    {
        $content = trim($content);

        if ($content === '') {
            return false;
        }

        return (bool) preg_match('/<\s*(?:!doctype|html|head|body|table|div|span|p|h[1-6]|a|tr|td|th|section)/i', $content);
    }

    protected function renderTemplate(?string $template, array $variables): ?string
    {
        if ($template === null) {
            return null;
        }

        $rendered = preg_replace_callback('/{{\s*(\w+)\s*}}/', function (array $matches) use ($variables): string {
            $key = $matches[1] ?? '';

            if ($key === '') {
                return $matches[0];
            }

            if (! array_key_exists($key, $variables)) {
                return $matches[0];
            }

            return (string) $variables[$key];
        }, $template);

        if (! is_string($rendered)) {
            return null;
        }

        return trim($rendered) === '' ? null : $rendered;
    }

    protected function splitTemplateLines(string $template): array
    {
        $lines = preg_split("/\r\n|\r|\n/", $template);

        if ($lines === false) {
            return [];
        }

        return array_map(static fn (string $line): string => rtrim($line, "\r"), $lines);
    }

    protected function sendWhatsApp(?string $phone, ?string $message): void
    {
        if (! $phone || ! $message) {
            return;
        }

        $config = config('form-transfer.notifications.whatsapp', []);

        if (! Arr::get($config, 'enabled')) {
            return;
        }

        $endpoint = Arr::get($config, 'endpoint');
        $apiKey = Arr::get($config, 'api_key');
        $sender = Arr::get($config, 'sender');

        $provider = strtolower(trim((string) Arr::get($config, 'provider', 'generic')));

        $missing = [];

        if (! $endpoint) {
            $missing[] = 'endpoint';
        }

        if (! $apiKey) {
            $missing[] = 'api_key';
        }

        if ($provider !== 'fonnte' && ! $sender) {
            $missing[] = 'sender';
        }

        if ($missing !== []) {
            Log::warning('FormTransfer WhatsApp notification skipped due to missing configuration.', [
                'provider' => $provider,
                'endpoint' => $endpoint,
                'api_key'  => $apiKey ? 'configured' : 'missing',
                'sender'   => $sender,
                'missing'  => $missing,
            ]);

            return;
        }

        $timeout = (int) ($config['timeout'] ?? 10);
        $delaySeconds = app(WhatsAppThrottleService::class)->getDispatchDelaySeconds();

        $pendingDispatch = SendWhatsAppNotification::dispatch(
            $phone,
            $message,
            $endpoint,
            $apiKey,
            (string) ($sender ?? ''),
            $timeout,
        );

        if ($delaySeconds > 0) {
            $pendingDispatch->delay($delaySeconds);
        }
    }
}
