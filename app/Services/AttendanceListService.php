<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceListService
{
    public function getListData(User $user, ?string $requestedDate): array
    {
        // 対象月の全日付を生成
        if ($requestedDate) {
            $date = Carbon::parse($requestedDate)->startOfMonth();
        } else {
            $date = now();
        }

        // 前月
        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        // 次月
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        // 対象月の全日付を生成
        $period = CarbonPeriod::create(
            $date->copy()->startOfMonth(),
            $date->copy()->endOfMonth()
        );

        // ログインユーザーの対象月の勤怠取得
        $attendanceRecords = $user->attendanceRecords()
            ->with('breaks')
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->orderBy('date')
            ->get();

        // 日付をキーにして対応付け
        $attendanceRecordsByDate = $attendanceRecords->keyBy(function ($attendanceRecord) {
            return $attendanceRecord->date->format('Y-m-d');
        });

        // 勤怠一覧情報取得
        $formattedAttendanceRecords = collect($period)->map(
            function ($day) use ($attendanceRecordsByDate) {
                $attendanceRecord = $attendanceRecordsByDate->get(
                    $day->format('Y-m-d')
                );

                if ($attendanceRecord === null) {
                    // 勤怠の記録がない日の場合

                    // 日付以外空欄で返却
                    return [
                        'id' => null,
                        'date' => $day->isoFormat('MM/DD(ddd)'),
                        'clock_in' => '',
                        'clock_out' => '',
                        'total_break_time' => '',
                        'total_time' => '',
                    ];
                }

                // 以下、勤怠の記録がある場合
                $totalBreakMinutes = $attendanceRecord->getTotalBreakMinutes();
                $totalWorkMinutes = $attendanceRecord->getTotalWorkMinutes();

                // 休憩（HH:MMで表示）
                $totalBreakTime = sprintf(
                    '%02d:%02d',
                    intdiv($totalBreakMinutes, 60),
                    $totalBreakMinutes % 60
                );

                // 合計（HH:MMで表示）
                $totalWorkTime = sprintf(
                    '%02d:%02d',
                    intdiv($totalWorkMinutes, 60),
                    $totalWorkMinutes % 60
                );

                // 終了済みの休憩が1件でも存在するか判定
                $hasCompletedBreak = $attendanceRecord->breaks->contains(function ($break) {
                    return $break->break_out !== null;
                });

                return [
                    'id' => $attendanceRecord->id,
                    'date' => $attendanceRecord->date->isoFormat('MM/DD(ddd)'),
                    'clock_in' => Carbon::parse($attendanceRecord->clock_in)->format('H:i'),
                    'clock_out' => $attendanceRecord->clock_out
                        ? Carbon::parse($attendanceRecord->clock_out)->format('H:i')
                        : '',
                    'total_break_time' => $hasCompletedBreak
                        ? $totalBreakTime
                        : '',
                    'total_time' => $attendanceRecord->clock_out
                        ? $totalWorkTime
                        : '',
                ];
            }
        );

        return [
            'date' => $date,
            'previousMonth' => $previousMonth,
            'nextMonth' => $nextMonth,
            'formattedAttendanceRecords' => $formattedAttendanceRecords,
        ];
    }
}
