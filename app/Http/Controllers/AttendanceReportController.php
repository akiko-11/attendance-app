<?php

namespace App\Http\Controllers;

use App\Services\AttendanceReportService;
use Illuminate\View\View;

class AttendanceReportController extends Controller
{
    /**
     * 認証ユーザーの勤怠レポートを取得して表示する。
     *
     * @param  AttendanceReportService  $attendanceReportService  勤怠レポートを取得するサービス
     * @return View 勤怠レポート画面
     */
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
