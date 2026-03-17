<?php

use Cesa\Shelf\Http\Controllers\AssetRequestController;
use Cesa\Shelf\Http\Controllers\PdfController;
use Cesa\Shelf\Http\Controllers\PrivateAttachmentDownloadController;
use Cesa\Shelf\Livewire\PublicAssetRequestApprovalPage;
use Cesa\Shelf\Livewire\PublicAssetRequestForm;
use Cesa\Shelf\Livewire\PublicAssetRequestIndex;
use Cesa\Shelf\Livewire\PublicAssetRequestProgressPage;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function (): void {
    Route::get('attachments/{type}/{record}/{attribute}/{index?}', PrivateAttachmentDownloadController::class)
        ->middleware('signed')
        ->whereNumber('record')
        ->name('shelf.attachments.download');

    Route::middleware('auth')->group(function (): void {
        Route::get('asset-transfer/{id}/download', [PdfController::class, 'downloadAssetTransfer'])
            ->whereNumber('id')
            ->name('asset-transfer.download');

        Route::get('task-completion/{id}/download', [PdfController::class, 'downloadTaskCompletion'])
            ->whereNumber('id')
            ->name('task-completion.download');

        Route::get('task-completion/{id}/preview', [PdfController::class, 'previewTaskCompletion'])
            ->whereNumber('id')
            ->name('task-completion.preview');
    });

    Route::prefix('asset-requests')->name('asset-requests.')->group(function (): void {
        Route::get('/', PublicAssetRequestIndex::class)->name('index');
        Route::get('progress/{uuid}', PublicAssetRequestProgressPage::class)->name('progress');
        Route::get('success/{uuid}', PublicAssetRequestProgressPage::class)->name('success');
        Route::get('approve/{token}', PublicAssetRequestApprovalPage::class)->name('show-approval');
        Route::post('approve/{token}', [AssetRequestController::class, 'processApproval'])->name('process-approval');
        Route::get('{type}', PublicAssetRequestForm::class)->name('create');
        Route::post('{type}', [AssetRequestController::class, 'store'])->name('store');
    });

    Route::prefix('public-asset-requests')->group(function (): void {
        Route::get('/', [AssetRequestController::class, 'legacyIndexRedirect']);
        Route::get('success/{uuid}', [AssetRequestController::class, 'legacySuccessRedirect']);
        Route::get('approve/{token}', [AssetRequestController::class, 'legacyApprovalRedirect']);
        Route::post('approve/{token}', [AssetRequestController::class, 'processApproval']);
        Route::post('{type}', [AssetRequestController::class, 'store']);
        Route::get('{type}', [AssetRequestController::class, 'legacyCreateRedirect']);
    });
});
