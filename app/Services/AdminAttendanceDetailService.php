<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use Carbon\Carbon;

class AdminAttendanceDetailService
{
    // 詳細情報取得
    public function getDetailData(int $attendanceId): array
    {
        // 対象勤怠とユーザー・休憩情報を取得
        $attendance = AttendanceRecord::with(['user', 'breaks'])
            ->findOrFail($attendanceId);

        // 休憩時刻を整形し、配列として取り出す
        $breaks = $attendance->breaks->map(function ($break) {
            return [
                'break_in' => $break->break_in
                    ? Carbon::parse($break->break_in)->format('H:i')
                    : '',
                'break_out' => $break->break_out
                    ? Carbon::parse($break->break_out)->format('H:i')
                    : '',
            ];
        })->values()->all();

        // Bladeに必要なキーを持つ配列を返却
        return [
            'user' => $attendance->user,
            'attendanceRecord' => [
                'id' => $attendance->id,
                'year' => $attendance->date->format('Y年'),
                'date' => $attendance->date->format('n月j日'),
                'clock_in' => $attendance->clock_in
                    ? Carbon::parse($attendance->clock_in)->format('H:i')
                    : '',
                'clock_out' => $attendance->clock_out
                    ? Carbon::parse($attendance->clock_out)->format('H:i')
                    : '',
                'breaks' => $breaks,
                'comment' => $attendance->comment,
            ],
        ];
    }
}
