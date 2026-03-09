<?php

use Cesa\Payroll\Http\Controllers\Api\PayrollController;
use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth:sanctum'])->prefix('api/payroll')->group(function () {
    Route::get('/', [PayrollController::class, 'index'])->name('api.payroll.index');
    Route::get('/{id}', [PayrollController::class, 'show'])->name('api.payroll.show');
});
