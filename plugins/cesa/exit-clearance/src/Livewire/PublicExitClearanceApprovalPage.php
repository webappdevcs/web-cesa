<?php

namespace Cesa\ExitClearance\Livewire;

use Cesa\ExitClearance\Models\Approver;
use Cesa\ExitClearance\Models\Request as ExitClearanceRequest;
use Cesa\ExitClearance\Services\ExitClearanceNotificationService;
use Cesa\ExitClearance\Services\ExitClearanceRequestService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Webkul\PluginManager\Package;

class PublicExitClearanceApprovalPage extends SimplePage
{
    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static string $layout = 'exit-clearance::layouts.form';

    protected string $view = 'exit-clearance::livewire.public-exit-clearance-approval-page';

    public ExitClearanceRequest $requestRecord;

    public Approver $approverRecord;

    public array $summary = [];

    public array $approvals = [];

    public array $currentApproval = [];

    public string $statusLabel = '';

    public bool $actionTaken = false;

    public ?array $data = [];

    protected ExitClearanceRequestService $requestService;

    protected ExitClearanceNotificationService $notificationService;

    public function boot(
        ExitClearanceRequestService $requestService,
        ExitClearanceNotificationService $notificationService,
    ): void {
        $this->requestService = $requestService;
        $this->notificationService = $notificationService;
    }

    public function mount(int|string $request, int|string $approver): void
    {
        if (! Package::isPluginInstalled('exit-clearance')) {
            abort(404);
        }

        $requestRecord = ExitClearanceRequest::query()
            ->with(['approvers', 'department'])
            ->find($request);

        $approverRecord = Approver::query()->find($approver);

        if (! $requestRecord || ! $approverRecord) {
            abort(404);
        }

        $pivot = $requestRecord->approvers->firstWhere('id', $approverRecord->id)?->pivot;

        if (! $pivot) {
            abort(404);
        }

        $this->requestRecord = $requestRecord;
        $this->approverRecord = $approverRecord;
        $this->currentApproval = [
            'status'      => $pivot->status,
            'notes'       => $pivot->notes,
            'approved_at' => $pivot->approved_at,
        ];

        $this->summary = $this->requestService->buildCategorizedSummary($this->requestRecord);
        $this->approvals = $this->requestService->buildApprovals($this->requestRecord);
        $this->statusLabel = $this->requestService->formatFormStatus($this->requestRecord->form_status);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('notes')
                    ->label(__('exit-clearance::app.public.approval.notes_label'))
                    ->rows(4),
            ])
            ->statePath('data');
    }

    public function approve(): void
    {
        if (! $this->isPendingApproval()) {
            Notification::make()
                ->title(__('exit-clearance::app.public.approval.cannot_process'))
                ->danger()
                ->send();

            return;
        }

        $notes = Arr::get($this->form->getState(), 'notes');

        $this->requestRecord->approvers()->updateExistingPivot($this->approverRecord->id, [
            'status'      => ExitClearanceRequestService::APPROVAL_APPROVED,
            'notes'       => $notes,
            'approved_at' => now(),
        ]);

        $this->requestRecord->refresh();
        $this->requestService->syncOverallStatus($this->requestRecord);
        $this->notificationService->notifyRequesterIfFinal($this->requestRecord);

        $this->refreshDisplayState();

        Notification::make()
            ->title(__('exit-clearance::app.public.approval.approved_success'))
            ->success()
            ->send();
    }

    public function reject(): void
    {
        if (! $this->isPendingApproval()) {
            Notification::make()
                ->title(__('exit-clearance::app.public.approval.cannot_process'))
                ->danger()
                ->send();

            return;
        }

        $notes = Arr::get($this->form->getState(), 'notes');

        $this->requestRecord->approvers()->updateExistingPivot($this->approverRecord->id, [
            'status'      => ExitClearanceRequestService::APPROVAL_REJECTED,
            'notes'       => $notes,
            'approved_at' => now(),
        ]);

        $this->requestRecord->refresh();
        $this->requestService->syncOverallStatus($this->requestRecord);
        $this->notificationService->notifyRequesterIfFinal($this->requestRecord);

        $this->refreshDisplayState();

        Notification::make()
            ->title(__('exit-clearance::app.public.approval.rejected_success'))
            ->danger()
            ->send();
    }

    public function isPendingApproval(): bool
    {
        $approvalStatus = $this->requestService->normalizeApprovalStatus($this->currentApproval['status'] ?? null);
        $formStatus = $this->requestService->normalizeFormStatus($this->requestRecord->form_status);

        return $approvalStatus === ExitClearanceRequestService::APPROVAL_PENDING && $formStatus === 'pending';
    }

    protected function refreshDisplayState(): void
    {
        $this->requestRecord->loadMissing('approvers', 'department');

        $pivot = $this->requestRecord->approvers->firstWhere('id', $this->approverRecord->id)?->pivot;
        $this->currentApproval = [
            'status'      => $pivot?->status,
            'notes'       => $pivot?->notes,
            'approved_at' => $pivot?->approved_at,
        ];

        $this->summary = $this->requestService->buildCategorizedSummary($this->requestRecord);
        $this->approvals = $this->requestService->buildApprovals($this->requestRecord);
        $this->statusLabel = $this->requestService->formatFormStatus($this->requestRecord->form_status);
        $this->actionTaken = true;
    }

    public function getHeading(): string
    {
        return __('exit-clearance::app.public.approval.heading');
    }

    public function getSubheading(): string
    {
        $name = $this->requestRecord->name ?? null;

        return $name
            ? __('exit-clearance::app.public.approval.subheading', ['name' => $name])
            : __('exit-clearance::app.public.approval.subheading_default');
    }

    public function hasLogo(): bool
    {
        return false;
    }
}
