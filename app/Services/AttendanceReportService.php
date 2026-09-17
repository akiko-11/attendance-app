<?php

namespace App\Services;

use App\Models\User;

class AttendanceReportService
{
    public function getReportData(User $user): array
    {
        $now = now();

        // 今月を含む過去6ヶ月
        $startDate = $now->copy()->startOfMonth()->subMonths(5);
        $endDate = $now->copy()->endOfMonth();

        // ログインユーザーの過去6ヶ月の勤怠を取得
        $attendanceRecords = $user->attendanceRecords()
            ->with('breaks')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        // 退勤済みの勤怠について、集計に使用する値をあらかじめ算出
        $completedAttendanceRecords = $attendanceRecords
            ->filter(
                fn ($attendanceRecord) => $attendanceRecord->clock_out !== null
            )
            ->map(function ($attendanceRecord) {
                return [
                    'record' => $attendanceRecord,
                    'month' => $attendanceRecord->date->format('Y-m'),
                    'work_minutes' => $attendanceRecord->getTotalWorkMinutes(),
                ];
            });

        // 総労働時間
        $totalWorkMinutes = $completedAttendanceRecords
            ->sum('work_minutes');

        // 総残業時間
        $totalOvertimeMinutes = $completedAttendanceRecords
            ->sum(
                fn ($item) => max($item['work_minutes'] - 480, 0)
            );

        $workDayCount = $completedAttendanceRecords->count();

        // 1日あたりの平均労働時間
        $avgWorkMinutes = $workDayCount > 0
            ? intdiv($totalWorkMinutes, $workDayCount)
            : 0;

        // 月ごとに退勤済み勤怠を分類
        $completedRecordsByMonth = $completedAttendanceRecords
            ->groupBy('month');

        // 今月を含む過去6ヶ月の月次推移
        $monthlyTrend = collect(range(5, 0))
            ->map(function ($i) use ($now, $completedRecordsByMonth) {
                $month = $now->copy()
                    ->startOfMonth()
                    ->subMonths($i);

                $monthKey = $month->format('Y-m');

                $monthlyRecords = $completedRecordsByMonth
                    ->get($monthKey, collect());

                return [
                    'month' => $monthKey,
                    'work_minutes' => $monthlyRecords
                        ->sum('work_minutes'),
                    'overtime_minutes' => $monthlyRecords
                        ->sum(
                            fn ($item) => max(
                                $item['work_minutes'] - 480,
                                0
                            )
                        ),
                ];
            })
            ->values()
            ->all();

        $currentMonth = $now->format('Y-m');

        // 今月の勤怠のみ取得
        $currentMonthRecords = $attendanceRecords->filter(
            fn ($attendanceRecord) => $attendanceRecord->date
                ->format('Y-m') === $currentMonth
        );

        // 09:00より後の出勤は遅刻
        $lateCount = $currentMonthRecords
            ->filter(
                fn ($attendanceRecord) => $attendanceRecord->clock_in > '09:00:00'
            )
            ->count();

        // 退勤済みの今月勤怠
        $completedCurrentMonthRecords = $currentMonthRecords
            ->filter(
                fn ($attendanceRecord) => $attendanceRecord->clock_out !== null
            );

        // 18:00より前の退勤は早退
        $earlyLeaveCount = $completedCurrentMonthRecords
            ->filter(
                fn ($attendanceRecord) => $attendanceRecord->clock_out < '18:00:00'
            )
            ->count();

        // 実労働時間が10時間を超えた勤怠
        $longWorkCount = $completedCurrentMonthRecords
            ->filter(
                fn ($attendanceRecord) => $attendanceRecord
                    ->getTotalWorkMinutes() > 600
            )
            ->count();

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
