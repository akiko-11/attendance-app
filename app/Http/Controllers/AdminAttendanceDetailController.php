<?php

namespace App\Http\Controllers;

use App\Services\AdminAttendanceDetailService;
use Illuminate\View\View;

class AdminAttendanceDetailController extends Controller
{
    /**
     * 指定された勤怠情報の詳細を取得して管理者向け画面に表示する。
     *
     * @param  AdminAttendanceDetailService  $adminAttendanceDetailService  勤怠詳細を取得するサービス
     * @param  int  $id  勤怠情報ID
     * @return View 管理者向け勤怠詳細画面
     */
    public function show(
        AdminAttendanceDetailService $adminAttendanceDetailService,
        int $id
    ): View {
        $data = $adminAttendanceDetailService->getDetailData($id);

        return view('admin.admin-detail', [
            'user' => $data['user'],
            'attendanceRecord' => $data['attendanceRecord'],
        ]);
    }
}
