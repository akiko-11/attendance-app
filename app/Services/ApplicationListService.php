<?php

namespace App\Services;

use App\Models\User;

class ApplicationListService
{
    public function getListData(User $user): array
    {
        // 対象ユーザーの修正申請と関連する勤怠レコードを取得
        $applications = $user->attendanceCorrectionRequests()
            ->with('attendanceRecord')
            ->get();

        // 申請一覧表示用にデータを整形
        $formattedApplications = $applications->map(function ($application) {
            return [
                'id' => $application->id,
                'approval_status' => $application->approval_status
                    ? '承認済み'
                    : '承認待ち',
                'date' => $application->attendanceRecord->date->format('Y/m/d'),
                'comment' => $application->comment,
                'application_date' => $application->created_at->format('Y/m/d'),
            ];
        });

        return [
            'formattedApplications' => $formattedApplications,
        ];
    }
}
