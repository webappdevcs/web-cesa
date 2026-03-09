<?php

use Cesa\Rekrutmen\Http\Controllers\Api\CareerController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->prefix('api')->group(function () {
    Route::get('/jobs', [CareerController::class, 'index'])->name('rekrutmen.jobs.index');
    Route::get('/jobs/{slug}', [CareerController::class, 'show'])->name('rekrutmen.jobs.detail');
    Route::post('/jobs/{slug}/apply', [CareerController::class, 'apply'])->name('rekrutmen.jobs.apply');
});
