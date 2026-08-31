<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;

class AdminAttendanceListService
{
    // 指定日の全ユーザーの勤怠一覧データを取得する
    public function getListData(?string $requestedDate): array
    {
        // 表示対象の日付を決定
        if ($requestedDate) {
            $date = Carbon::parse($requestedDate);
        } else {
            $date = now();
        }

        // 前日
        $previousDay = $date->copy()
            ->subDay()
            ->format('Y-m-d');

        // 翌日
        $nextDay = $date->copy()
            ->addDay()
            ->format('Y-m-d');

        // 一覧に表示するユーザーを取得
        $users = User::all();

        // 指定日の勤怠情報を取得
        $attendanceRecords = AttendanceRecord::with('breaks')
            ->whereDate('date', $date->toDateString())
            ->get();

        // 一覧表示用に休憩時間・合計時間を追加
        $attendanceRecords = $attendanceRecords->map(function ($attendance) {
            $totalBreakMinutes = $attendance->getTotalBreakMinutes();
            $totalWorkMinutes = $attendance->getTotalWorkMinutes();

            // 終了済みの休憩が1件でも存在するか判定
            $hasCompletedBreak = $attendance->breaks->contains(function ($break) {
                return $break->break_out !== null;
            });

            // 合計休憩時間（HH:MMで表示）
            $attendance->total_break_time = $hasCompletedBreak
                ? sprintf(
                    '%02d:%02d',
                    intdiv($totalBreakMinutes, 60),
                    $totalBreakMinutes % 60
                )
                : '';

            // 合計勤務時間（HH:MMで表示）
            $attendance->total_time = $attendance->clock_out !== null
                ? sprintf(
                    '%02d:%02d',
                    intdiv($totalWorkMinutes, 60),
                    $totalWorkMinutes % 60
                )
                : '';

            return $attendance;
        });

        // Bladeへ渡すデータを返す
        return [
            'date' => $date,
            'previousDay' => $previousDay,
            'nextDay' => $nextDay,
            'users' => $users,
            'attendanceRecords' => $attendanceRecords,
        ];
    }
}
