<?php

namespace Cesa\Shelf\Mail;

use Cesa\Shelf\Models\AssetRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AssetRequestSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AssetRequest $assetRequest,
    ) {}

    public function envelope(): Envelope
    {
        $typeLabel = AssetRequest::getRequestTypeLabel($this->assetRequest->request_type);

        return new Envelope(
            subject: "Konfirmasi: {$typeLabel} - {$this->assetRequest->item_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'shelf::emails.asset_requests.submitted',
            with: [
                'assetRequest' => $this->assetRequest,
                'detailUrl'    => route('asset-requests.success', $this->assetRequest->uuid),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
