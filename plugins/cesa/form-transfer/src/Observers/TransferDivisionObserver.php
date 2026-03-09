<?php

namespace Cesa\FormTransfer\Observers;

use Cesa\FormTransfer\Models\TransferDivision;
use Cesa\FormTransfer\Services\ReferenceDataService;

/**
 * Observer for TransferDivision model to handle cache invalidation.
 */
class TransferDivisionObserver
{
    public function __construct(
        protected ReferenceDataService $referenceDataService,
    ) {}

    /**
     * Handle the TransferDivision "saved" event.
     */
    public function saved(TransferDivision $division): void
    {
        if ($division->form_transfer_id) {
            $this->referenceDataService->invalidateDivisionCache($division->form_transfer_id);
        }
    }

    /**
     * Handle the TransferDivision "deleted" event.
     */
    public function deleted(TransferDivision $division): void
    {
        if ($division->form_transfer_id) {
            $this->referenceDataService->invalidateDivisionCache($division->form_transfer_id);
        }
    }

    /**
     * Handle the TransferDivision "restored" event.
     */
    public function restored(TransferDivision $division): void
    {
        if ($division->form_transfer_id) {
            $this->referenceDataService->invalidateDivisionCache($division->form_transfer_id);
        }
    }
}
