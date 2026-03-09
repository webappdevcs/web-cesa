<?php

use Cesa\Rekrutmen\Http\Controllers\JobApplicationAttachmentDownloadController;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('man-power', \Cesa\Rekrutmen\Livewire\PublicRequestManPowerForm::class)
        ->middleware(SetCacheHeaders::using([
            'no_store'        => true,
            'no_cache'        => true,
            'must_revalidate' => true,
            'max_age'         => 0,
            'private'         => true,
        ]))
        ->name('rekrutmen.public.request-man-power.form');
});

Route::middleware(['web', 'auth', 'signed'])->group(function () {
    Route::get('rekrutmen/job-applications/{jobApplication}/files/{attachment}', JobApplicationAttachmentDownloadController::class)
        ->name('rekrutmen.job-applications.attachments.download');
});
