<?php

use App\Http\Controllers\AdminApplicationApprovalController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminStaffAttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionRequestController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

// 未ログインユーザー
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', function () {
        return view('admin.admin-login');
    })->name('admin.login');

    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])
        ->name('admin.login.store');
});

// ログイン済み一般ユーザー
Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/list', [AttendanceController::class, 'list']);
    Route::get('/stamp_correction_request/list', [AttendanceCorrectionRequestController::class, 'index']);
});

// ログイン済み管理者
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index']);
    Route::get('/admin/staff/list', [AdminStaffController::class, 'index']);
    Route::get('/admin/attendance/staff/{id}', [AdminStaffAttendanceController::class, 'index']);
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [
        AdminApplicationApprovalController::class, 'show',
    ]);
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [
        AdminApplicationApprovalController::class, 'approve',
    ]);
    Route::post('/admin/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('admin.logout');
});
