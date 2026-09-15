<?php

use App\Http\Controllers\Api\V1\AttendanceRecordController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // 読み取り系：認証不要
    Route::get('/attendance-records', [
        AttendanceRecordController::class,
        'index',
    ]);

    Route::get('/attendance-records/{attendanceRecord}', [
        AttendanceRecordController::class,
        'show',
    ]);

    // 書き込み系：Sanctum認証必須
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/attendance-records', [
            AttendanceRecordController::class,
            'store',
        ]);

        Route::match(['put', 'patch'], '/attendance-records/{attendanceRecord}', [
            AttendanceRecordController::class,
            'update',
        ]);

        Route::delete('/attendance-records/{attendanceRecord}', [
            AttendanceRecordController::class,
            'destroy',
        ]);
    });
});
