<?php

namespace Cesa\FormTransfer\Observers;

use Cesa\FormTransfer\Models\TransferBank;
use Cesa\FormTransfer\Services\ReferenceDataService;

/**
 * Observer for TransferBank model to handle cache invalidation.
 */
class TransferBankObserver
{
    /**
     * Handle the TransferBank "saved" event.
     */
    public function saved(TransferBank $bank): void
    {
        app(ReferenceDataService::class)->invalidateBankCache();
    }

    /**
     * Handle the TransferBank "deleted" event.
     */
    public function deleted(TransferBank $bank): void
    {
        app(ReferenceDataService::class)->invalidateBankCache();
    }

    /**
     * Handle the TransferBank "restored" event.
     */
    public function restored(TransferBank $bank): void
    {
        app(ReferenceDataService::class)->invalidateBankCache();
    }
}
