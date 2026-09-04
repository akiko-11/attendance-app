<?php

namespace App\Services;

use App\Models\AttendanceCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Fluent;

class AdminApplicationApprovalService
{
    // 修正申請の詳細表示用データを取得
    public function getDetailData(int $applicationId): array
    {
        $application = AttendanceCorrectionRequest::with([
            'user',
            'proposalBreaks',
        ])->findOrFail($applicationId);

        // この申請を行ったユーザー
        $user = $application->user;

        // 提供Bladeに合わせて表示用データを整形
        $formattedApplication = new Fluent([
            'id' => $application->id,
            'new_date' => $application->new_date,
            'new_clock_in' => $application->new_clock_in
                ? Carbon::parse($application->new_clock_in)->format('H:i')
                : '',
            'new_clock_out' => $application->new_clock_out
                ? Carbon::parse($application->new_clock_out)->format('H:i')
                : '',
            'proposalBreaks' => $application->proposalBreaks,
            'comment' => $application->comment,
            'approval_status' => $application->approval_status
                ? '承認済み'
                : '承認待ち',
        ]);

        return [
            'application' => $formattedApplication,
            'user' => $user,
        ];
    }

    // 修正申請を承認し、勤怠・休憩へ反映
    public function approve(int $applicationId): void
    {
        DB::transaction(function () use ($applicationId) {
            // 同じ申請が同時に承認されないようにロック
            $application = AttendanceCorrectionRequest::with([
                'proposalBreaks',
            ])
                ->lockForUpdate()
                ->findOrFail($applicationId);

            // 承認済みなら再度反映しない
            if ($application->approval_status) {
                return;
            }

            // 更新対象の勤怠を取得してロック
            $attendance = $application->attendanceRecord()
                ->lockForUpdate()
                ->firstOrFail();

            // 申請内容を元の勤怠へ反映
            $attendance->update([
                'date' => $application->new_date,
                'clock_in' => $application->new_clock_in,
                'clock_out' => $application->new_clock_out,
                'comment' => $application->comment,
            ]);

            // 対象の勤怠に紐づく休憩だけを削除
            $attendance->breaks()->delete();

            // 申請された休憩全件で置き換える
            foreach ($application->proposalBreaks as $proposalBreak) {
                $attendance->breaks()->create([
                    'break_in' => $proposalBreak->break_in,
                    'break_out' => $proposalBreak->break_out,
                ]);
            }

            // 勤怠・休憩の反映後に承認済みへ更新
            $application->update([
                'approval_status' => true,
            ]);
        });
    }
}
