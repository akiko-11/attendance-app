<?php

namespace App\Http\Controllers;

use App\Services\AttendanceListService;
use App\Services\AttendanceStampService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * 勤怠打刻画面を表示する。
     *
     * @return View 勤怠打刻画面
     */
    public function index(): View
    {
        $user = Auth::user();
        $now = now();

        $formattedDate = $now->isoFormat('YYYY年M月D日(ddd)');
        $formattedTime = $now->format('H:i');

        return view('user.attendance-register', compact(
            'user',
            'formattedDate',
            'formattedTime'
        ));
    }

    /**
     * 認証ユーザーの勤怠打刻を処理する。
     *
     * @param  Request  $request  打刻操作のリクエスト
     * @param  AttendanceStampService  $attendanceStampService  勤怠打刻処理を行うサービス
     * @return RedirectResponse 勤怠打刻画面へのリダイレクト
     */
    public function store(
        Request $request,
        AttendanceStampService $attendanceStampService
    ): RedirectResponse {
        $user = Auth::user();

        $attendanceStampService->stamp($user, $request->action);

        return redirect('/attendance');
    }

    // 勤怠一覧情報取得処理
    /**
     * 認証ユーザーの勤怠一覧を取得して表示する。
     *
     * @param  Request  $request  表示対象月の情報を含むリクエスト
     * @param  AttendanceListService  $attendanceListService  勤怠一覧を取得するサービス
     * @return View 勤怠一覧画面
     */
    public function list(
        Request $request,
        AttendanceListService $attendanceListService
    ): View {
        $user = Auth::user();

        $data = $attendanceListService->getListData(
            $user,
            $request->date
        );

        return view('user.user-attendance-list', $data);
    }
}
