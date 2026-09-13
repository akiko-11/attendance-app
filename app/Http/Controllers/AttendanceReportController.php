<?php

namespace App\Http\Controllers;

use App\Services\AttendanceReportService;
use Illuminate\View\View;

class AttendanceReportController extends Controller
{
    public function index(
        AttendanceReportService $attendanceReportService
    ): View {

        // ログインユーザーの勤怠情報のみ取得
        $data = $attendanceReportService->getReportData(
            auth()->user()
        );

        return view('reports.index', $data);
    }
}
