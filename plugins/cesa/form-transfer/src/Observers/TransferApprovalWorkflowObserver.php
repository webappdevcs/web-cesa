<?php

namespace Cesa\FormTransfer\Observers;

use Cesa\FormTransfer\Models\TransferApprovalWorkflow;
use Cesa\FormTransfer\Services\ReferenceDataService;

/**
 * Observer for TransferApprovalWorkflow model to handle cache invalidation.
 */
class TransferApprovalWorkflowObserver
{
    public function __construct(
        protected ReferenceDataService $referenceDataService,
    ) {}

    /**
     * Handle the TransferApprovalWorkflow "saved" event.
     */
    public function saved(TransferApprovalWorkflow $workflow): void
    {
        if ($workflow->form_transfer_id) {
            $this->referenceDataService->invalidateWorkflowCache(
                $workflow->form_transfer_id,
                $workflow->division_id
            );
        }
    }

    /**
     * Handle the TransferApprovalWorkflow "deleted" event.
     */
    public function deleted(TransferApprovalWorkflow $workflow): void
    {
        if ($workflow->form_transfer_id) {
            $this->referenceDataService->invalidateWorkflowCache(
                $workflow->form_transfer_id,
                $workflow->division_id
            );
        }
    }

    /**
     * Handle the TransferApprovalWorkflow "restored" event.
     */
    public function restored(TransferApprovalWorkflow $workflow): void
    {
        if ($workflow->form_transfer_id) {
            $this->referenceDataService->invalidateWorkflowCache(
                $workflow->form_transfer_id,
                $workflow->division_id
            );
        }
    }
}
