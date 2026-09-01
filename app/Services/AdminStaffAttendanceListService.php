<?php

namespace App\Services;

use App\Models\User;

class AdminStaffAttendanceListService
{
    public function __construct(
        private AttendanceListService $attendanceListService
    ) {}

    public function getListData(
        int $userId,
        ?string $requestedDate
    ): array {
        // 管理者IDの直接指定を防ぐため、取得対象を一般ユーザーに限定する
        $user = User::where('admin_status', false)
            ->findOrFail($userId);

        // 一般ユーザー画面と同じ月次勤怠の取得・整形処理を再利用する
        $data = $this->attendanceListService->getListData(
            $user,
            $requestedDate
        );

        return [
            'user' => $user,
            ...$data,
        ];
    }
}
