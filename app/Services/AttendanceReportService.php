<?php

namespace App\Services;

use App\Models\User;

class AttendanceReportService
{
    public function getReportData(User $user): array
    {
        $now = now();

        // 今月を含む過去6ヶ月
        $startDate = $now->copy()->subMonths(5)->startOfMonth();
        $endDate = $now->copy()->endOfMonth();

        // ログインユーザーの過去6ヶ月の勤怠を取得
        $attendanceRecords = $user->attendanceRecords()
            ->with('breaks')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        // 退勤済みの勤怠のみ取得
        $completedAttendanceRecords = $attendanceRecords->filter(
            fn ($attendanceRecord) => $attendanceRecord->clock_out !== null
        );

        $currentMonth = $now->format('Y-m');

        // 今月の勤怠のみ取得
        $currentMonthRecords = $attendanceRecords->filter(
            fn ($attendanceRecord) => $attendanceRecord->date->format('Y-m') === $currentMonth
        );

        $totalWorkMinutes = 0;
        $totalOvertimeMinutes = 0;

        foreach ($completedAttendanceRecords as $attendanceRecord) {
            $workMinutes = $attendanceRecord->getTotalWorkMinutes();

            // 総労働時間
            $totalWorkMinutes += $workMinutes;

            // 1日8時間を超えた分を残業時間として加算
            $totalOvertimeMinutes += max($workMinutes - 480, 0);
        }

        $workDayCount = $completedAttendanceRecords->count();

        // 1日あたりの平均労働時間
        $avgWorkMinutes = $workDayCount > 0
            ? intdiv($totalWorkMinutes, $workDayCount)
            : 0;

        // 以下、月次推移の処理
        $monthlyTrend = [];

        for ($i = 5; $i >= 0; $i--) {
            // 今月を含む過去6か月分の年月を生成
            $month = $now->copy()->subMonths($i);
            $monthKey = $month->format('Y-m');

            $monthlyWorkMinutes = 0;
            $monthlyOvertimeMinutes = 0;

            // 該当月の退勤済み勤怠のみ取得
            $monthlyRecords = $completedAttendanceRecords->filter(
                fn ($attendanceRecord) => $attendanceRecord->date->format('Y-m') === $monthKey
            );

            foreach ($monthlyRecords as $attendanceRecord) {
                $workMinutes = $attendanceRecord->getTotalWorkMinutes();

                $monthlyWorkMinutes += $workMinutes;

                // 該当月の各日の残業時間を加算
                $monthlyOvertimeMinutes += max($workMinutes - 480, 0);
            }

            $monthlyTrend[] = [
                'month' => $monthKey,
                'work_minutes' => $monthlyWorkMinutes,
                'overtime_minutes' => $monthlyOvertimeMinutes,
            ];
        }

        // 以下、今月の異常検知処理

        $lateCount = 0;
        $earlyLeaveCount = 0;
        $longWorkCount = 0;

        foreach ($currentMonthRecords as $attendanceRecord) {
            // 09:00より後の出勤は遅刻
            if ($attendanceRecord->clock_in > '09:00:00') {
                $lateCount++;
            }

            // 未退勤の場合、早退・長時間労働は判定しない
            if ($attendanceRecord->clock_out === null) {
                continue;
            }

            // 18:00より前の退勤は早退
            if ($attendanceRecord->clock_out < '18:00:00') {
                $earlyLeaveCount++;
            }

            $workMinutes = $attendanceRecord->getTotalWorkMinutes();

            // 実労働時間が10時間を超えた場合
            if ($workMinutes > 600) {
                $longWorkCount++;
            }
        }

        return [
            'summary' => [
                'total_work_minutes' => $totalWorkMinutes,
                'total_overtime_minutes' => $totalOvertimeMinutes,
                'avg_work_minutes' => $avgWorkMinutes,
            ],
            'monthlyTrend' => $monthlyTrend,
            'anomalies' => [
                'late_count' => $lateCount,
                'early_leave_count' => $earlyLeaveCount,
                'long_work_count' => $longWorkCount,
            ],
        ];
    }
}
