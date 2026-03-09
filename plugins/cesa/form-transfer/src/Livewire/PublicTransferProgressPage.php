<?php

namespace Cesa\FormTransfer\Livewire;

use Cesa\FormTransfer\Services\TransferApprovalNotificationService;
use Cesa\FormTransfer\Services\TransferRequestService;
use Filament\Pages\SimplePage;
use Webkul\PluginManager\Package;

class PublicTransferProgressPage extends SimplePage
{
    protected static string $layout = 'form-transfer::layouts.form';

    protected string $view = 'form-transfer::livewire.public-transfer-progress-page';

    public array $approvals = [];

    public array $summary = [];

    public string $statusLabel = '';

    protected TransferRequestService $transferRequestService;

    protected TransferApprovalNotificationService $notificationService;

    public function boot(
        TransferRequestService $transferRequestService,
        TransferApprovalNotificationService $notificationService,
    ): void {
        $this->transferRequestService = $transferRequestService;
        $this->notificationService = $notificationService;
    }

    public function mount(string $response): void
    {
        if (! Package::isPluginInstalled('form-transfer')) {
            abort(404);
        }

        $request = $this->transferRequestService->findByStatusResponseId($response);

        if (! $request) {
            abort(404);
        }

        $this->summary = $this->notificationService->getRequestSummary($request);
        $this->approvals = $request->approvals ?? [];
        $this->statusLabel = $this->summary['status'] ?? '';
    }

    public function getHeading(): string
    {
        return __('form-transfer::public.progress.heading');
    }

    public function getSubheading(): string
    {
        return __('form-transfer::public.progress.description');
    }

    public function hasLogo(): bool
    {
        return false;
    }
}
