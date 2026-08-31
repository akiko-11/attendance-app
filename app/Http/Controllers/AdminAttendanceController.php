<?php

namespace App\Http\Controllers;

use App\Services\AdminAttendanceListService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAttendanceController extends Controller
{
    // 勤怠一覧情報取得処理
    public function index(
        Request $request,
        AdminAttendanceListService $adminAttendanceListService
    ): View {
        $data = $adminAttendanceListService->getListData(
            $request->date
        );

        return view('admin.admin-attendance-list', $data);
    }
}
