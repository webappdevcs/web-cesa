<?php

namespace Cesa\Shelf\Livewire;

use Cesa\Shelf\Enums\ApprovalStatus;
use Cesa\Shelf\Enums\RequestStatus;
use Cesa\Shelf\Models\AssetRequest;
use Cesa\Shelf\Models\RequestApproval;
use Cesa\Shelf\Services\PublicAssetRequestService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Throwable;
use Webkul\PluginManager\Package;

class PublicAssetRequestApprovalPage extends SimplePage implements HasForms
{
    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static string $layout = 'shelf::layouts.form';

    protected string $view = 'shelf::livewire.public-asset-request-approval-page';

    public ?array $data = [];

    public RequestApproval $approval;

    public AssetRequest $assetRequest;

    public string $requestTypeLabel;

    public bool $hasResponded;

    public bool $isCurrentApproval;

    public bool $requestClosed;

    public bool $canRespond;

    protected PublicAssetRequestService $publicAssetRequestService;

    public function boot(PublicAssetRequestService $publicAssetRequestService): void
    {
        $this->publicAssetRequestService = $publicAssetRequestService;
    }

    public function mount(string $token): void
    {
        if (! Package::isPluginInstalled('shelf')) {
            abort(404);
        }

        $this->approval = RequestApproval::query()
            ->where('token', $token)
            ->with(['assetRequest' => fn ($query) => $query->withTrashed()->with('approvals')])
            ->firstOrFail();

        $this->assetRequest = $this->approval->assetRequest;

        abort_if($this->assetRequest === null, 404);

        $currentApproval = $this->assetRequest->approvals
            ->first(fn (RequestApproval $step): bool => $step->status === ApprovalStatus::Pending);

        $this->requestTypeLabel = AssetRequest::getRequestTypeLabel($this->assetRequest->request_type);
        $this->requestClosed = $this->assetRequest->trashed() || $this->assetRequest->status !== RequestStatus::Pending;
        $this->hasResponded = $this->approval->status !== ApprovalStatus::Pending;
        $this->isCurrentApproval = $currentApproval?->is($this->approval) ?? false;
        $this->canRespond = (! $this->hasResponded) && (! $this->requestClosed) && $this->isCurrentApproval;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(5)
                    ->placeholder('Opsional, tambahkan catatan bila diperlukan.'),
            ])
            ->statePath('data');
    }

    public function approve(): void
    {
        $this->handleApprovalAction('approve');
    }

    public function reject(): void
    {
        $this->handleApprovalAction('reject');
    }

    public function getHeading(): string
    {
        return 'Approval Request Aset';
    }

    public function getSubheading(): string
    {
        return 'Tinjau data pengajuan berikut dan berikan keputusan approval Anda.';
    }

    public function hasLogo(): bool
    {
        return false;
    }

    private function handleApprovalAction(string $action): void
    {
        try {
            $result = $this->publicAssetRequestService->processApproval(
                $this->approval->token,
                $action,
                $this->form->getState()['notes'] ?? null,
            );

            if (($result['type'] ?? null) === 'success') {
                Notification::make()
                    ->title((string) ($result['message'] ?? 'Aksi berhasil diproses.'))
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title((string) ($result['message'] ?? 'Aksi tidak dapat diproses.'))
                    ->warning()
                    ->send();
            }

            $this->refreshState();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Approval tidak dapat diproses saat ini.')
                ->body('Silakan coba lagi beberapa saat lagi.')
                ->danger()
                ->send();
        }
    }

    private function refreshState(): void
    {
        $this->approval->refresh();
        $this->assetRequest->refresh()->load('approvals');

        $currentApproval = $this->assetRequest->approvals
            ->first(fn (RequestApproval $step): bool => $step->status === ApprovalStatus::Pending);

        $this->requestClosed = $this->assetRequest->trashed() || $this->assetRequest->status !== RequestStatus::Pending;
        $this->hasResponded = $this->approval->status !== ApprovalStatus::Pending;
        $this->isCurrentApproval = $currentApproval?->is($this->approval) ?? false;
        $this->canRespond = (! $this->hasResponded) && (! $this->requestClosed) && $this->isCurrentApproval;
    }
}
