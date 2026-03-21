<?php

use Cesa\Presensi\Http\Controllers\API\AttendanceController;
use Cesa\Presensi\Http\Controllers\API\LeaveController;
use Cesa\Presensi\Http\Controllers\API\OvertimeController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/api/v1/presensi')
    ->middleware(['api', 'auth:sanctum'])
    ->name('admin.api.v1.presensi.')
    ->group(function (): void {
        Route::get('attendance/today', [AttendanceController::class, 'getAttendanceToday'])->name('attendance.today');
        Route::get('schedule', [AttendanceController::class, 'getSchedule'])->name('schedule.show');
        Route::post('attendance/check-in', [AttendanceController::class, 'checkIn'])
            ->middleware('throttle:5,5')
            ->name('attendance.check-in');
        Route::post('attendance/check-out', [AttendanceController::class, 'checkOut'])
            ->middleware('throttle:5,5')
            ->name('attendance.check-out');
        Route::get('attendance/history/{month}/{year}', [AttendanceController::class, 'getAttendanceByMonthAndYear'])
            ->name('attendance.history');
        Route::post('schedule/ban', [AttendanceController::class, 'banned'])->name('schedule.ban');
        Route::get('photo', [AttendanceController::class, 'getPhoto'])->name('photo.show');

        Route::apiResource('leaves', LeaveController::class)
            ->only(['index', 'store']);

        Route::apiResource('overtimes', OvertimeController::class)
            ->only(['index', 'store']);
    });
