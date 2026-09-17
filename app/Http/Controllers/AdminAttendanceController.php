<?php

namespace App\Http\Controllers;

use App\Services\AdminAttendanceListService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAttendanceController extends Controller
{
    /**
     * 管理者向けの勤怠一覧を取得して表示する。
     *
     * @param  Request  $request  表示対象日の情報を含むリクエスト
     * @param  AdminAttendanceListService  $adminAttendanceListService  勤怠一覧を取得するサービス
     * @return View 管理者向け勤怠一覧画面
     */
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
