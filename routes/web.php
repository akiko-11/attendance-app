<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionRequestController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::middleware('guest')->group(function () {
    Route::get('/admin/login', function () {
        return view('admin.admin-login');
    })->name('admin.login');

    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])
        ->name('admin.login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index']);

    Route::post('/attendance', [AttendanceController::class, 'store']);

    Route::get('/attendance/list', [AttendanceController::class, 'list']);

    Route::get('/stamp_correction_request/list', [AttendanceCorrectionRequestController::class, 'index']);

    Route::post('/admin/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('admin.logout');
});
