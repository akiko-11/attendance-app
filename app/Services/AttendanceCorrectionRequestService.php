<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class AttendanceCorrectionRequestService
{
    public function store(
        User $user,
        int $attendanceId,
        array $data
    ): void {

        DB::transaction(function () use ($user, $attendanceId, $data) {

            // 本人の勤怠を取得し、申請処理中はロックする
            $attendance = $user->attendanceRecords()
                ->lockForUpdate()
                ->findOrFail($attendanceId);

            // 承認待ち申請があれば、新たな申請を受け付けない
            if ($attendance->attendanceCorrectionRequests()
                ->where('approval_status', false)
                ->exists()) {
                return;
            }

            // 修正申請を承認待ちとして保存
            $application = $attendance->attendanceCorrectionRequests()->create([
                'user_id' => $user->id,
                'new_date' => $attendance->date,
                'new_clock_in' => $data['new_clock_in'],
                'new_clock_out' => $data['new_clock_out'] ?? null,
                'comment' => $data['comment'],
                'approval_status' => false,
            ]);

            // 空欄を除いた申請後の休憩をすべて保存
            foreach ($data['new_break_in'] ?? [] as $index => $breakIn) {
                // 追加用の空欄は保存しない
                if ($breakIn === null || $breakIn === '') {
                    continue;
                }

                $application->proposalBreaks()->create([
                    'break_in' => $breakIn,
                    'break_out' => $data['new_break_out'][$index] ?? null,
                ]);
            }
        });
    }
}
