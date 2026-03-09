<?php

namespace Cesa\FormTransfer\Services;

use Cesa\FormTransfer\Enums\ApprovalStatus;
use Cesa\FormTransfer\Models\TransferApprovalWorkflow;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Service for managing approval workflow operations.
 *
 * Handles approval step generation, status updates, and workflow progression.
 */
class ApprovalWorkflowService
{
    /**
     * Prepare approval steps from workflow template.
     *
     * @param  array<int, array{label: ?string, name: ?string, email: ?string, status: ?string, task_id: ?string}>  $currentApprovals  Existing approvals to preserve
     * @return array<int, array{label: string, name: ?string, email: ?string, title: ?string, phone: ?string, status: string, comments: ?string, noted_at: ?string, notes: ?string, task_id: string, task_token: string, notified_at: ?string, is_mandatory: bool, has_next: bool, sort_order: int}>
     */
    public function prepareApprovalsFromWorkflow(int $workflowId, array $currentApprovals = []): array
    {
        try {
            $workflow = TransferApprovalWorkflow::query()->find($workflowId);

            if (! $workflow) {
                logger()->warning('Workflow not found', ['workflow_id' => $workflowId]);

                return [];
            }

            $steps = collect($workflow->steps ?? [])->values();

            if ($steps->isEmpty()) {
                logger()->warning('Workflow has no steps', ['workflow_id' => $workflowId]);

                return [];
            }

            $normalizedSteps = $this->normalizeWorkflowSteps($steps->all());

            return $this->finalizeApprovals($normalizedSteps, $currentApprovals);
        } catch (\Exception $e) {
            logger()->error('Error preparing approvals from workflow', [
                'workflow_id'       => $workflowId,
                'current_approvals' => $currentApprovals,
                'error'             => $e->getMessage(),
                'trace'             => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Update approval status for a specific task.
     *
     * @param  array<int, array{task_id: string, status: string}>  $approvals  Current approval list
     * @param  array{status: string, notes?: string, noted_at?: string}  $updateData  Status update data
     * @return array<int, array{task_id: string, status: string}>|null Updated approvals or null if task not found
     */
    public function updateApprovalStatus(array $approvals, string $taskId, array $updateData): ?array
    {
        $taskIndex = $this->findTaskIndex($approvals, $taskId);

        if ($taskIndex === null) {
            logger()->warning('Task ID not found in approvals', [
                'task_id'   => $taskId,
                'approvals' => $approvals,
            ]);

            return null;
        }

        $approvals[$taskIndex] = array_merge($approvals[$taskIndex], $updateData);

        return $approvals;
    }

    /**
     * Advance workflow to next approval step.
     *
     * Updates current step status to approved and sets next step to pending.
     *
     * @param  array<int, array{status: string, has_next: bool}>  $approvals  Current approval list
     * @return array<int, array{status: string}> Updated approvals
     */
    public function advanceToNextApproval(array $approvals, int $currentStepIndex): array
    {
        if (! isset($approvals[$currentStepIndex])) {
            return $approvals;
        }

        // Set current step to approved
        $approvals[$currentStepIndex]['status'] = ApprovalStatus::APPROVED->value;

        // Set next step to pending if exists
        $nextIndex = $currentStepIndex + 1;
        if (isset($approvals[$nextIndex])) {
            $approvals[$nextIndex]['status'] = ApprovalStatus::PENDING->value;
            $approvals[$nextIndex]['notified_at'] = Carbon::now()->toISOString();
        }

        return $approvals;
    }

    /**
     * Determine overall approval status from all approval steps.
     *
     * @param  array<int, array{status: string, is_mandatory: bool}>  $approvals  List of approval steps
     * @return string Overall status (pending, approved, rejected, waiting)
     */
    public function determineOverallStatus(array $approvals): string
    {
        if (empty($approvals)) {
            return ApprovalStatus::PENDING->value;
        }

        $mandatoryApprovals = collect($approvals)->filter(fn ($approval) => $approval['is_mandatory'] ?? true);

        // Check for any rejections in mandatory steps
        if ($mandatoryApprovals->contains(fn ($approval) => $approval['status'] === ApprovalStatus::DITOLAK->value)) {
            return ApprovalStatus::DITOLAK->value;
        }

        // Check if all mandatory approvals are approved
        $allMandatoryApproved = $mandatoryApprovals->every(
            fn ($approval) => $approval['status'] === ApprovalStatus::APPROVED->value
        );

        if ($allMandatoryApproved) {
            return ApprovalStatus::APPROVED->value;
        }

        // Check if any pending
        if ($mandatoryApprovals->contains(fn ($approval) => $approval['status'] === ApprovalStatus::PENDING->value)) {
            return ApprovalStatus::PENDING->value;
        }

        return ApprovalStatus::WAITING->value;
    }

    /**
     * Get the current pending approval step.
     *
     * @param  array<int, array{status: string}>  $approvals  List of approval steps
     * @return array{index: int, approval: array}|null Pending approval or null
     */
    public function getCurrentPendingApproval(array $approvals): ?array
    {
        foreach ($approvals as $index => $approval) {
            if ($approval['status'] === ApprovalStatus::PENDING->value) {
                return ['index' => $index, 'approval' => $approval];
            }
        }

        return null;
    }

    /**
     * Validate if approval action is allowed.
     */
    public function canPerformAction(string $currentStatus, string $newStatus): bool
    {
        $allowedTransitions = [
            ApprovalStatus::PENDING->value => [
                ApprovalStatus::APPROVED->value,
                ApprovalStatus::DITOLAK->value,
            ],
            ApprovalStatus::WAITING->value => [
                ApprovalStatus::PENDING->value,
            ],
        ];

        return in_array($newStatus, $allowedTransitions[$currentStatus] ?? [], true);
    }

    /**
     * Normalize workflow steps from template.
     *
     * @param  array<int, array<string, mixed>>  $steps  Raw workflow steps
     * @return array<int, array{label: ?string, name: ?string, email: ?string, title: ?string, phone: ?string, is_mandatory: bool, sort_order: int}>
     */
    protected function normalizeWorkflowSteps(array $steps): array
    {
        return collect($steps)
            ->map(function (array $step): array {
                return [
                    'label'        => Arr::get($step, 'label'),
                    'name'         => Arr::get($step, 'default_name'),
                    'email'        => Arr::get($step, 'default_email'),
                    'title'        => Arr::get($step, 'default_title'),
                    'phone'        => Arr::get($step, 'default_phone'),
                    'is_mandatory' => Arr::get($step, 'is_mandatory', true),
                    'sort_order'   => Arr::get($step, 'sort_order'),
                ];
            })
            ->all();
    }

    /**
     * Finalize approvals by merging template with existing data.
     *
     * @param  array<int, array<string, mixed>>  $steps  Normalized workflow steps
     * @param  array<int, array<string, mixed>>  $currentApprovals  Existing approvals
     * @return array<int, array{label: string, name: ?string, email: ?string, title: ?string, phone: ?string, status: string, comments: ?string, noted_at: ?string, notes: ?string, task_id: string, task_token: string, notified_at: ?string, is_mandatory: bool, has_next: bool, sort_order: int}>
     */
    protected function finalizeApprovals(array $steps, array $currentApprovals = []): array
    {
        $count = count($steps);

        if ($count === 0) {
            return [];
        }

        return collect($steps)
            ->values()
            ->map(function (array $step, int $index) use ($currentApprovals, $count): array {
                $existing = $currentApprovals[$index] ?? [];
                $status = $existing['status']
                    ?? ($index === 0
                        ? ApprovalStatus::PENDING->value
                        : ApprovalStatus::WAITING->value);
                $generatedTaskId = $existing['task_id'] ?? Str::uuid()->toString();

                return [
                    'label'        => $existing['label'] ?? $step['label'] ?? 'Approval '.($index + 1),
                    'name'         => $existing['name'] ?? $step['name'] ?? null,
                    'email'        => $existing['email'] ?? $step['email'] ?? null,
                    'title'        => $existing['title'] ?? $step['title'] ?? null,
                    'phone'        => $existing['phone'] ?? $step['phone'] ?? null,
                    'status'       => $status,
                    'comments'     => $existing['comments'] ?? null,
                    'noted_at'     => $existing['noted_at'] ?? null,
                    'notes'        => $existing['notes'] ?? null,
                    'task_id'      => $generatedTaskId,
                    'task_token'   => $existing['task_token'] ?? base64_encode($generatedTaskId),
                    'notified_at'  => $existing['notified_at'] ?? ($index === 0 ? Carbon::now()->toISOString() : null),
                    'is_mandatory' => $existing['is_mandatory'] ?? ($step['is_mandatory'] ?? true),
                    'has_next'     => $existing['has_next'] ?? ($index < ($count - 1)),
                    'sort_order'   => $existing['sort_order'] ?? Arr::get($step, 'sort_order', $index + 1),
                ];
            })
            ->all();
    }

    /**
     * Find task index in approvals array.
     *
     * @param  array<int, array{task_id: string}>  $approvals  Approvals list
     */
    protected function findTaskIndex(array $approvals, string $taskId): ?int
    {
        foreach ($approvals as $index => $approval) {
            if (($approval['task_id'] ?? null) === $taskId) {
                return $index;
            }
        }

        return null;
    }
}
