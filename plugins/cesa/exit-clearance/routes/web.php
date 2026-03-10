<?php

use Cesa\ExitClearance\Http\Controllers\PublicAttachmentDownloadController;
use Cesa\ExitClearance\Livewire\PublicExitClearanceApprovalPage;
use Cesa\ExitClearance\Livewire\PublicExitClearanceProgressPage;
use Cesa\ExitClearance\Livewire\PublicExitClearanceRequestForm;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function (): void {
    Route::get('exit-clearance', PublicExitClearanceRequestForm::class)
        ->name('exit-clearance.public.form');

    Route::get('exit-clearance/approval/{request}/{approver}', PublicExitClearanceApprovalPage::class)
        ->middleware('signed')
        ->name('exit-clearance.public.approval');

    Route::get('exit-clearance/progress/{response}', PublicExitClearanceProgressPage::class)
        ->name('exit-clearance.public.progress');

    Route::get('exit-clearance/files/{response}/{attachment}', PublicAttachmentDownloadController::class)
        ->middleware('signed')
        ->name('exit-clearance.public.attachments.download');
});
