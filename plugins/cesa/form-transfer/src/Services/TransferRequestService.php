<?php

namespace Cesa\FormTransfer\Services;

use Cesa\FormTransfer\Models\TransferRequest;

/**
 * Service for managing transfer requests.
 *
 * Orchestrates transfer request operations using repository and other services.
 */
class TransferRequestService
{
    public function __construct(
        protected ReferenceDataService $referenceDataService,
        protected ApprovalWorkflowService $approvalWorkflowService,
    ) {}

    /**
     * Get division options for a form transfer.
     *
     * @return array<int, string>
     */
    public function getDivisionOptions(?int $formTransferId): array
    {
        return $this->referenceDataService->getDivisionOptions($formTransferId);
    }

    /**
     * Get bank options (global reference).
     *
     * @return array<int, string>
     */
    public function getBankOptions(): array
    {
        return $this->referenceDataService->getBankOptions();
    }

    /**
     * Get reference note options for a form transfer.
     *
     * @return array<string, string>
     */
    public function getReferenceNoteOptions(?int $formTransferId): array
    {
        return $this->referenceDataService->getReferenceNoteOptions($formTransferId);
    }

    /**
     * Get workflow options for a form transfer and division.
     *
     * @return array<int, string>
     */
    public function getWorkflowOptions(?int $formTransferId, ?int $divisionId): array
    {
        return $this->referenceDataService->getWorkflowOptions($formTransferId, $divisionId);
    }

    /**
     * Resolve division name by ID.
     */
    public function resolveDivisionName(?string $divisionId): ?string
    {
        if (! $divisionId) {
            return null;
        }

        return $this->referenceDataService->findDivision((int) $divisionId)?->name;
    }

    /**
     * Resolve bank by ID.
     */
    public function resolveBank(mixed $bankId): ?\Cesa\FormTransfer\Models\TransferBank
    {
        if (! is_numeric($bankId)) {
            return null;
        }

        return $this->referenceDataService->findBank((int) $bankId);
    }

    /**
     * Resolve reference note by ID.
     */
    public function resolveReferenceNote(?string $referenceNoteId): ?\Cesa\FormTransfer\Models\TransferReferenceNote
    {
        if (! $referenceNoteId) {
            return null;
        }

        return $this->referenceDataService->findReferenceNote((int) $referenceNoteId);
    }

    /**
     * Find transfer request by approval task ID.
     */
    public function findByApprovalTaskId(string $taskId): ?TransferRequest
    {
        try {
            $connection = TransferRequest::query()->getConnection()->getDriverName();

            if ($connection === 'sqlite') {
                return TransferRequest::query()
                    ->whereNotNull('approvals')
                    ->get()
                    ->first(function (TransferRequest $request) use ($taskId): bool {
                        return collect($request->approvals ?? [])
                            ->contains(fn (array $approval): bool => ($approval['task_id'] ?? null) === $taskId);
                    });
            }

            return TransferRequest::query()
                ->whereRaw("JSON_SEARCH(approvals, 'one', ?, NULL, '\$[*].task_id') IS NOT NULL", [$taskId])
                ->first();
        } catch (\Throwable $exception) {
            logger()->error('Error finding transfer request by task id.', [
                'task_id' => $taskId,
                'error'   => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find transfer request by status response ID.
     */
    public function findByStatusResponseId(string $statusResponseId): ?TransferRequest
    {
        try {
            return TransferRequest::query()
                ->where('status_response_id', $statusResponseId)
                ->first();
        } catch (\Throwable $exception) {
            logger()->error('Error finding transfer request by status response id.', [
                'status_response_id' => $statusResponseId,
                'error'              => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Prepare approval steps from workflow.
     *
     * @param  array<int, array{label: ?string, name: ?string, email: ?string, status: ?string, task_id: ?string}>  $currentApprovals
     * @return array<int, array{label: string, name: ?string, email: ?string, title: ?string, phone: ?string, status: string, comments: ?string, noted_at: ?string, notes: ?string, task_id: string, task_token: string, notified_at: ?string, is_mandatory: bool, has_next: bool, sort_order: int}>
     */
    public function prepareApprovalsFromWorkflow(int $workflowId, array $currentApprovals = []): array
    {
        return $this->approvalWorkflowService->prepareApprovalsFromWorkflow($workflowId, $currentApprovals);
    }
}
