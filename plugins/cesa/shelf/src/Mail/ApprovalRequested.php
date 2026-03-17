<?php

namespace Cesa\Shelf\Mail;

use Cesa\Shelf\Models\AssetRequest;
use Cesa\Shelf\Models\RequestApproval;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApprovalRequested extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AssetRequest $assetRequest,
        public RequestApproval $approval,
    ) {}

    public function envelope(): Envelope
    {
        $typeLabel = AssetRequest::getRequestTypeLabel($this->assetRequest->request_type);

        return new Envelope(
            subject: "Perlu Persetujuan: {$typeLabel} - {$this->assetRequest->item_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'shelf::emails.asset_requests.approval-requested',
            with: [
                'assetRequest' => $this->assetRequest,
                'approval'     => $this->approval,
                'approvalUrl'  => route('asset-requests.show-approval', $this->approval->token),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
