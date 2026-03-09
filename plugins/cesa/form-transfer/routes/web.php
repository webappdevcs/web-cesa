<?php

use Cesa\FormTransfer\Http\Controllers\PublicAttachmentDownloadController;
use Cesa\FormTransfer\Livewire\PublicTransferApprovalPage;
use Cesa\FormTransfer\Livewire\PublicTransferProgressPage;
use Cesa\FormTransfer\Livewire\PublicTransferRequestForm;
use Cesa\FormTransfer\Livewire\PublicTransferRequestIndex;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function (): void {
    Route::get('transfer-requests', PublicTransferRequestIndex::class)
        ->name('form-transfer.public.index');

    Route::get('transfer-requests/approval/{task}', PublicTransferApprovalPage::class)
        ->name('form-transfer.public.approval');

    Route::get('transfer-requests/progress/{response}', PublicTransferProgressPage::class)
        ->name('form-transfer.public.progress');

    Route::get('transfer-requests/{formTransfer}', PublicTransferRequestForm::class)
        ->name('form-transfer.public.form');

    Route::get('transfer-requests/files/{statusResponseId}/{attachment}', PublicAttachmentDownloadController::class)
        ->middleware('signed')
        ->name('form-transfer.public.attachments.download');
});
