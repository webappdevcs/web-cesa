<?php

use Cesa\Presensi\Http\Controllers\API\AttendanceController;
use Cesa\Presensi\Http\Controllers\API\AuthController;
use Cesa\Presensi\Http\Controllers\API\LeaveController;
use Cesa\Presensi\Http\Controllers\API\OvertimeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->middleware('api')->group(function () {
    Route::post('/presensi/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('api.presensi.login');
});

Route::prefix('api')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::get('/get-attendance-today', [AttendanceController::class, 'getAttendanceToday'])->name('api.presensi.get_attendance_today');
    Route::get('/get-schedule', [AttendanceController::class, 'getSchedule'])->name('api.presensi.get_schedule');
    Route::post('/store-attendance', [AttendanceController::class, 'store'])->middleware('throttle:5,5')->name('api.presensi.store_attendance');
    Route::get('/get-attendance-by-month-year/{month}/{year}', [AttendanceController::class, 'getAttendanceByMonthAndYear'])->name('api.presensi.get_attendance_by_month_and_year');
    Route::post('/banned', [AttendanceController::class, 'banned'])->name('api.presensi.banned');
    Route::get('/get-photo', [AttendanceController::class, 'getPhoto'])->name('api.presensi.get_photo');

    Route::get('/leaves', [LeaveController::class, 'index'])->name('api.presensi.leaves.index');
    Route::post('/leaves', [LeaveController::class, 'store'])->name('api.presensi.leaves.store');

    Route::get('/overtimes', [OvertimeController::class, 'index'])->name('api.presensi.overtimes.index');
    Route::post('/overtimes', [OvertimeController::class, 'store'])->name('api.presensi.overtimes.store');

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
