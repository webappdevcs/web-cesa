<?php

namespace Cesa\FormTransfer\Observers;

use Cesa\FormTransfer\Models\TransferReferenceNote;
use Cesa\FormTransfer\Services\ReferenceDataService;

/**
 * Observer for TransferReferenceNote model to handle cache invalidation.
 */
class TransferReferenceNoteObserver
{
    public function __construct(
        protected ReferenceDataService $referenceDataService,
    ) {}

    /**
     * Handle the TransferReferenceNote "saved" event.
     */
    public function saved(TransferReferenceNote $referenceNote): void
    {
        if ($referenceNote->form_transfer_id) {
            $this->referenceDataService->invalidateReferenceNoteCache($referenceNote->form_transfer_id);
        }
    }

    /**
     * Handle the TransferReferenceNote "deleted" event.
     */
    public function deleted(TransferReferenceNote $referenceNote): void
    {
        if ($referenceNote->form_transfer_id) {
            $this->referenceDataService->invalidateReferenceNoteCache($referenceNote->form_transfer_id);
        }
    }

    /**
     * Handle the TransferReferenceNote "restored" event.
     */
    public function restored(TransferReferenceNote $referenceNote): void
    {
        if ($referenceNote->form_transfer_id) {
            $this->referenceDataService->invalidateReferenceNoteCache($referenceNote->form_transfer_id);
        }
    }
}
