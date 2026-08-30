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

    // 勤怠打刻処理
    public function store(
        Request $request,
        AttendanceStampService $attendanceStampService
    ): RedirectResponse {
        $user = Auth::user();

        $attendanceStampService->stamp($user, $request->action);

        return redirect('/attendance');
    }

    // 勤怠一覧情報取得処理
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
