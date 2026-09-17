<?php

namespace App\Http\Controllers;

use App\Services\AttendanceDetailService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceDetailController extends Controller
{
    /**
     * 認証ユーザーの指定された勤怠詳細を取得して表示する。
     *
     * @param  AttendanceDetailService  $attendanceDetailService  勤怠詳細を取得するサービス
     * @param  int  $id  勤怠情報ID
     * @return View 勤怠詳細画面
     */
    public function show(
        AttendanceDetailService $attendanceDetailService,
        int $id
    ): View {
        $user = Auth::user();

        $data = $attendanceDetailService->getDetailData($user, $id);

        return view('user.user-detail', [
            'user' => $user,
            'data' => $data,
        ]);
    }
}
