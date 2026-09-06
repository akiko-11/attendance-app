<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;

class AttendanceDetailService
{
    public function getDetailData(User $user, int $attendanceId): array
    {
        // ログインユーザー本人の勤怠から、対象IDを取得
        $attendance = $user->attendanceRecords()
            ->with('breaks')
            ->findOrFail($attendanceId);

        // 対象勤怠の承認待ち申請を取得
        $application = $attendance->attendanceCorrectionRequests()
            ->with('proposalBreaks')
            ->where('approval_status', false)
            ->first();

        // 勤怠詳細画面に表示する情報の取得
        $clockIn = $application
        ? $application->new_clock_in
        : $attendance->clock_in;

        $clockOut = $application
            ? $application->new_clock_out
            : $attendance->clock_out;

        $displayBreaks = $application
            ? $application->proposalBreaks
            : $attendance->breaks;

        $comment = $application
            ? $application->comment
            : $attendance->comment;

        // 休憩時刻を整形し、配列として取り出す
        $breaks = $displayBreaks->map(function ($break) {
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
            'id' => $attendance->id,
            'year' => $attendance->date->format('Y年'),
            'date' => $attendance->date->format('n月j日'),
            'clock_in' => $clockIn
                ? Carbon::parse($clockIn)->format('H:i')
                : '',
            'clock_out' => $clockOut
                ? Carbon::parse($clockOut)->format('H:i')
                : '',
            'breaks' => $breaks,
            'comment' => $comment,
            'application' => $application,
        ];
    }
}
