<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
{
    /**
     * 管理者ユーザーにすべての操作を許可する。
     *
     * @param  User  $user  認可対象のユーザー
     * @param  string  $ability  実行しようとしている操作
     * @return bool|null 管理者の場合はtrue、それ以外は個別の認可判定へ進むためnull
     */
    public function before(
        User $user,
        string $ability
    ): ?bool {
        if ($user->admin_status) {
            return true;
        }

        return null;
    }

    /**
     * 勤怠情報を更新できるか判定する。
     *
     * @param  User  $user  認可対象のユーザー
     * @param  AttendanceRecord  $attendanceRecord  更新対象の勤怠情報
     * @return bool 本人の勤怠情報であればtrue
     */
    public function update(
        User $user,
        AttendanceRecord $attendanceRecord
    ): bool {
        return $user->id === $attendanceRecord->user_id;
    }

    /**
     * 勤怠情報を削除できるか判定する。
     *
     * @param  User  $user  認可対象のユーザー
     * @param  AttendanceRecord  $attendanceRecord  削除対象の勤怠情報
     * @return bool 本人の勤怠情報であればtrue
     */
    public function delete(
        User $user,
        AttendanceRecord $attendanceRecord
    ): bool {
        return $user->id === $attendanceRecord->user_id;
    }
}
