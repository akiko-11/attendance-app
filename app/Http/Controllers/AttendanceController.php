<?php

namespace App\Http\Controllers;

use App\Services\AttendanceListService;
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
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($request->action === 'clock_in') {
            // 出勤

            // 同日の勤怠レコードがなければ出勤時刻を登録する
            // すでに存在する場合は新規作成しない
            $user->attendanceRecords()->firstOrCreate(
                [
                    'date' => today()->toDateString(),
                ],
                [
                    'clock_in' => now()->format('H:i:s'),
                ]
            );

        } elseif ($request->action === 'break_in') {
            // 休憩入

            // 今日の勤怠を取得
            $attendance = $user->attendanceRecords()
                ->whereDate('date', today())
                ->first();

            // 休憩レコードを作成
            if ($attendance) {
                $attendance->breaks()->create([
                    'break_in' => now()->format('H:i:s'),
                ]);
            }

        } elseif ($request->action === 'break_out') {
            // 休憩戻

            // 今日の勤怠を取得
            $attendance = $user->attendanceRecords()
                ->whereDate('date', today())
                ->first();

            // 未終了の最新休憩レコードを取得
            if ($attendance) {
                $break = $attendance->breaks()
                    ->whereNull('break_out')
                    ->latest()
                    ->first();

                // 休憩戻時刻を更新
                if ($break) {
                    $break->update([
                        'break_out' => now()->format('H:i:s'),
                    ]);
                }
            }

        } elseif ($request->action === 'clock_out') {
            // 退勤

            // 今日の勤怠を取得
            $attendance = $user->attendanceRecords()
                ->whereDate('date', today())
                ->first();

            // 未退勤の場合、退勤時刻を更新
            if ($attendance && $attendance->clock_out === null) {
                $attendance->update([
                    'clock_out' => now()->format('H:i:s'),
                ]);
            }
        }

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
