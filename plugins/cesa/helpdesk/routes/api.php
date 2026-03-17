<?php

use Cesa\Helpdesk\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/api/v1/helpdesk')
    ->middleware(['api', 'auth:sanctum'])
    ->name('admin.api.v1.helpdesk.')
    ->group(function (): void {
        Route::get('meta', [TicketController::class, 'metadata'])->name('meta');

        Route::apiResource('tickets', TicketController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy']);

        Route::post('tickets/{ticket}/comments', [TicketController::class, 'storeComment'])
            ->name('tickets.comments.store');
    });
