<?php

use App\Http\Controllers\AdminApplicationApprovalController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminAttendanceDetailController;
use App\Http\Controllers\AdminStaffAttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceCorrectionRequestController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

// 未ログインユーザー
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create']);
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/admin/login', function () {
        return view('admin.admin-login');
    })->name('admin.login');
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])
        ->name('admin.login.store');
});

// ログイン済みユーザー
Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::get('/attendance/list', [AttendanceController::class, 'list']);
    Route::post('/attendance/{id}', [
        AttendanceCorrectionRequestController::class, 'store',
    ])->whereNumber('id');
    Route::get('/application/{id}', [
        AttendanceCorrectionRequestController::class, 'show',
    ])->whereNumber('id');
    Route::get('/stamp_correction_request/list', [AttendanceCorrectionRequestController::class, 'index']);
    Route::get('/attendance/detail/{id}', [
        AttendanceDetailController::class, 'show',
    ])->whereNumber('id');
    // 提供Bladeの詳細リンクから、ユーザー種別に応じた仕様URLへ転送
    Route::get('/attendance/{id}', function (int $id) {
        if (auth()->user()->admin_status) {
            return redirect('/admin/attendance/'.$id);
        }

        return redirect('/attendance/detail/'.$id);
    })->whereNumber('id');
});

// ログイン済み管理者
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index']);
    Route::get('/admin/attendance/{id}', [
        AdminAttendanceDetailController::class, 'show',
    ])->whereNumber('id');
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
