<?php

use Cesa\Kepegawaian\Http\Controllers\Api\V1\EmployeeRegistryController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/api/v1/kepegawaian')
    ->middleware(['api', 'auth:sanctum'])
    ->name('admin.api.v1.kepegawaian.')
    ->group(function (): void {
        Route::get('employees', [EmployeeRegistryController::class, 'index'])
            ->name('employees.index');
        Route::get('employees/resolve', [EmployeeRegistryController::class, 'resolve'])
            ->name('employees.resolve');
        Route::get('employees/{employee:uuid}', [EmployeeRegistryController::class, 'show'])
            ->whereUuid('employee')
            ->name('employees.show');
    });
