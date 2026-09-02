<?php

namespace App\Services;

use App\Models\AttendanceCorrectionRequest;
use Illuminate\Support\Fluent;

class AdminApplicationListService
{
    // 全一般ユーザーの修正申請を取得
    public function getListData(): array
    {
        $applications = AttendanceCorrectionRequest::with([
            'user',
            'attendanceRecord',
        ])
        // 一般ユーザーの申請に限定
            ->whereHas('user', function ($query) {
                $query->where('admin_status', false);
            })
            ->get();

        // 申請一覧表示用にデータを整形
        $applications = $applications->map(function ($application) {
            return new Fluent([
                'id' => $application->id,
                'approval_status' => $application->approval_status
                    ? '承認済み'
                    : '承認待ち',
                'user' => $application->user,
                'AttendanceRecord' => $application->attendanceRecord,
                'comment' => $application->comment,
                'application_date' => $application->created_at,
            ]);
        });

        return [
            'applications' => $applications,
        ];
    }
}
