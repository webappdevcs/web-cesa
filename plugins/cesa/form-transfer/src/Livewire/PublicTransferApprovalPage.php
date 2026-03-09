<?php

namespace Cesa\FormTransfer\Livewire;

use Cesa\FormTransfer\Enums\ApprovalStatus;
use Cesa\FormTransfer\Enums\TransferRequestApprovalStatus;
use Cesa\FormTransfer\Models\TransferRequest;
use Cesa\FormTransfer\Services\TransferApprovalNotificationService;
use Cesa\FormTransfer\Services\TransferRequestService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Webkul\PluginManager\Package;

class PublicTransferApprovalPage extends SimplePage implements HasForms
{
    use InteractsWithFormActions;
    use InteractsWithForms;

    protected static string $layout = 'form-transfer::layouts.form';

    protected string $view = 'form-transfer::livewire.public-transfer-approval-page';

    public TransferRequest $transferRequest;

    public array $approvals = [];

    public array $currentApproval = [];

    public int $currentApprovalIndex = 0;

    public array $summary = [];

    public string $statusLabel = '';

    public bool $actionTaken = false;

    public ?array $data = [];

    protected TransferRequestService $transferRequestService;

    protected TransferApprovalNotificationService $notificationService;

    public function boot(
        TransferRequestService $transferRequestService,
        TransferApprovalNotificationService $notificationService,
    ): void {
        $this->transferRequestService = $transferRequestService;
        $this->notificationService = $notificationService;
    }

    public function mount(string $task): void
    {
        if (! Package::isPluginInstalled('form-transfer')) {
            abort(404);
        }

        $request = $this->transferRequestService->findByApprovalTaskId($task);

        if (! $request) {
            abort(404);
        }

        $this->transferRequest = $request;
        $this->approvals = $request->approvals ?? [];
        $this->currentApprovalIndex = $this->locateApprovalIndex($task, $this->approvals);
        $this->summary = $this->notificationService->getRequestSummary($request);
        $this->statusLabel = $this->summary['status'] ?? '';

        if ($this->currentApprovalIndex === -1) {
            abort(404);
        }

        $this->currentApproval = $this->approvals[$this->currentApprovalIndex];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('comments')
                ->label(__('form-transfer::public.form.actions.comments'))
                ->rows(4)
                ->placeholder(__('form-transfer::public.form.actions.comments_placeholder')),
        ])->statePath('data');
    }

    public function approve(): void
    {
        if (! $this->isPendingApproval()) {
            Notification::make()
                ->title(__('form-transfer::public.form.actions.invalid_state'))
                ->body(__('form-transfer::public.form.actions.already_processed_body'))
                ->warning()
                ->send();

            return;
        }

        if ($this->isRateLimited()) {
            return;
        }

        $state = $this->form->getState();

        $approvals = $this->approvals;
        $approvals[$this->currentApprovalIndex]['status'] = ApprovalStatus::APPROVED->value;
        $approvals[$this->currentApprovalIndex]['comments'] = Arr::get($state, 'comments');
        $approvals[$this->currentApprovalIndex]['noted_at'] = Carbon::now()->toISOString();

        $nextApproval = $approvals[$this->currentApprovalIndex + 1] ?? null;

        if ($nextApproval) {
            $approvals[$this->currentApprovalIndex + 1]['status'] = ApprovalStatus::PENDING->value;
            $approvals[$this->currentApprovalIndex + 1]['notified_at'] = Carbon::now()->toISOString();
            $this->transferRequest->approval_status = TransferRequestApprovalStatus::PENDING;
        } else {
            $this->transferRequest->approval_status = TransferRequestApprovalStatus::APPROVED;
        }

        $this->transferRequest->approvals = array_values($approvals);
        $this->transferRequest->save();
        $this->transferRequest->refresh();

        $this->approvals = $this->transferRequest->approvals ?? [];
        $this->currentApproval = $this->approvals[$this->currentApprovalIndex];
        $this->actionTaken = true;

        if ($nextApproval) {
            $next = $this->approvals[$this->currentApprovalIndex + 1] ?? null;

            if ($next) {
                $this->notificationService->notifyApprover($this->transferRequest, $next, $this->approvals);
            }
        }

        $this->notificationService->notifyRequesterForFinalStatus($this->transferRequest);

        Notification::make()
            ->title(__('form-transfer::public.form.actions.approved'))
            ->success()
            ->send();

        $this->dispatch('form-processing-finished');
    }

    public function reject(): void
    {
        if (! $this->isPendingApproval()) {
            Notification::make()
                ->title(__('form-transfer::public.form.actions.invalid_state'))
                ->body(__('form-transfer::public.form.actions.already_processed_body'))
                ->warning()
                ->send();

            return;
        }

        if ($this->isRateLimited()) {
            return;
        }

        $state = $this->form->getState();

        $approvals = $this->approvals;
        $approvals[$this->currentApprovalIndex]['status'] = ApprovalStatus::DITOLAK->value;
        $approvals[$this->currentApprovalIndex]['comments'] = Arr::get($state, 'comments');
        $approvals[$this->currentApprovalIndex]['noted_at'] = Carbon::now()->toISOString();

        // Mark remaining approvals as waiting
        foreach (array_slice($approvals, $this->currentApprovalIndex + 1) as $index => $approval) {
            $approvals[$this->currentApprovalIndex + 1 + $index]['status'] = ApprovalStatus::WAITING->value;
        }

        $this->transferRequest->approvals = array_values($approvals);
        $this->transferRequest->approval_status = TransferRequestApprovalStatus::REJECTED;
        $this->transferRequest->save();
        $this->transferRequest->refresh();

        $this->approvals = $this->transferRequest->approvals ?? [];
        $this->currentApproval = $this->approvals[$this->currentApprovalIndex];
        $this->actionTaken = true;

        $this->notificationService->notifyRequesterForFinalStatus($this->transferRequest);

        Notification::make()
            ->title(__('form-transfer::public.form.actions.rejected'))
            ->danger()
            ->send();

        $this->dispatch('form-processing-finished');
    }

    public function getHeading(): string
    {
        return __('form-transfer::public.form.actions.heading', [
            'form' => $this->transferRequest->formTransfer?->name,
        ]);
    }

    public function getSubheading(): string
    {
        return __('form-transfer::public.form.actions.subheading', [
            'requester' => $this->transferRequest->requester_name,
        ]);
    }

    public function hasLogo(): bool
    {
        return false;
    }

    protected function locateApprovalIndex(string $taskId, array $approvals): int
    {
        foreach ($approvals as $index => $approval) {
            if (($approval['task_id'] ?? null) === $taskId) {
                return $index;
            }
        }

        return -1;
    }

    public function isPendingApproval(): bool
    {
        return ($this->currentApproval['status'] ?? null) === ApprovalStatus::PENDING->value;
    }

    /**
     * Check if the current request is rate limited.
     */
    protected function isRateLimited(): bool
    {
        $key = $this->buildApprovalRateLimitKey();
        $maxAttempts = 5;
        $decaySeconds = 60;

        if (! RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            RateLimiter::hit($key, $decaySeconds);

            return false;
        }

        $secondsRemaining = max(1, RateLimiter::availableIn($key));

        Notification::make()
            ->title(__('form-transfer::public.form.actions.rate_limit.title'))
            ->body(__('form-transfer::public.form.actions.rate_limit.body', [
                'seconds' => $secondsRemaining,
            ]))
            ->warning()
            ->send();

        return true;
    }

    /**
     * Build the rate limit key for the current approval action.
     */
    protected function buildApprovalRateLimitKey(): string
    {
        $taskId = $this->currentApproval['task_id'] ?? 'unknown';
        $ipAddress = request()?->ip() ?: 'guest';

        return sprintf('form-transfer:approval:%s:%s', $taskId, $ipAddress);
    }
}
