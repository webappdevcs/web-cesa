<?php

use Illuminate\Support\Facades\Route;
use Webkul\Security\Http\Controllers\API\V1\AuthController;

// Authentication routes (public)
Route::name('admin.api.v1.')->prefix('admin/api/v1')->group(function () {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login');
});

// Protected routes (require authentication)
Route::name('admin.api.v1.')->prefix('admin/api/v1')->middleware(['auth:sanctum'])->group(function () {
    Route::get('me', [AuthController::class, 'me'])->name('me');
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
});
