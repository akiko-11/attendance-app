<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceStampService
{
    // 打刻処理
    public function stamp(User $user, string $action): void
    {
        if ($action === 'clock_in') {
            $this->clockIn($user);
        } elseif ($action === 'break_in') {
            $this->breakIn($user);
        } elseif ($action === 'break_out') {
            $this->breakOut($user);
        } elseif ($action === 'clock_out') {
            $this->clockOut($user);
        }
    }

    // 出勤処理
    private function clockIn(User $user): void
    {
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
    }

    // 休憩開始処理
    private function breakIn(User $user): void
    {
        // 今日の勤怠を取得
        $attendance = $this->getTodayAttendance($user);

        // 休憩レコードを作成
        if ($attendance) {
            $attendance->breaks()->create([
                'break_in' => now()->format('H:i:s'),
            ]);
        }
    }

    // 休憩終了処理
    private function breakOut(User $user): void
    {
        // 今日の勤怠を取得
        $attendance = $this->getTodayAttendance($user);

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
    }

    // 退勤処理
    private function clockOut(User $user): void
    {
        // 今日の勤怠を取得
        $attendance = $this->getTodayAttendance($user);

        // 未退勤の場合、退勤時刻を更新
        if ($attendance && $attendance->clock_out === null) {
            $attendance->update([
                'clock_out' => now()->format('H:i:s'),
            ]);
        }
    }

    // 今日の勤怠を取得
    private function getTodayAttendance(User $user): ?AttendanceRecord
    {
        return $user->attendanceRecords()
            ->whereDate('date', today())
            ->first();
    }
}
