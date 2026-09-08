<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;

class AdminAttendanceUpdateService
{
    public function update(int $attendanceId, array $data): void
    {
        DB::transaction(function () use ($attendanceId, $data) {

            // 対象勤怠を取得し、更新中はロックする
            $attendance = AttendanceRecord::lockForUpdate()
                ->findOrFail($attendanceId);

            // 勤怠情報を直接更新
            $attendance->update([
                'clock_in' => $data['new_clock_in'],
                'clock_out' => $data['new_clock_out'] ?? null,
                'comment' => $data['comment'],
            ]);

            // 現在の休憩情報を削除
            $attendance->breaks()->delete();

            // 入力された休憩情報を保存
            foreach ($data['new_break_in'] ?? [] as $index => $breakIn) {
                if ($breakIn === null || $breakIn === '') {
                    continue;
                }

                $attendance->breaks()->create([
                    'break_in' => $breakIn,
                    'break_out' => $data['new_break_out'][$index] ?? null,
                ]);
            }
        });
    }
}
