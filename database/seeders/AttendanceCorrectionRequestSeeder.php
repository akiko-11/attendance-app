<?php

namespace Database\Seeders;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\ProposalBreak;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendanceCorrectionRequestSeeder extends Seeder
{
    public function run(): void
    {
        $user1 = User::where('email', 'user1@example.com')->firstOrFail();
        $user2 = User::where('email', 'user2@example.com')->firstOrFail();

        // ユーザー1の直近の勤怠3件取得
        $user1Attendances = AttendanceRecord::where('user_id', $user1->id)
            ->orderByDesc('date')
            ->limit(3)
            ->get();

        // ユーザー2の直近の勤怠3件取得
        $user2Attendances = AttendanceRecord::where('user_id', $user2->id)
            ->orderByDesc('date')
            ->limit(3)
            ->get();

        // 以下、ユーザー1の修正申請作成
        // 直近1件目、承認待ち：出勤時刻修正
        $correctionRequest = AttendanceCorrectionRequest::factory()->create([
            'user_id' => $user1->id,
            'attendance_record_id' => $user1Attendances[0]->id,
            'new_date' => $user1Attendances[0]->date->toDateString(),
            'new_clock_in' => '09:10',
            'new_clock_out' => '18:00',
            'comment' => '出勤時間修正のため',
            'approval_status' => false,
        ]);

        ProposalBreak::factory()->create([
            'attendance_correction_request_id' => $correctionRequest->id,
        ]);

        // 直近2件目、承認待ち：休憩時刻修正
        $correctionRequest = AttendanceCorrectionRequest::factory()->create([
            'user_id' => $user1->id,
            'attendance_record_id' => $user1Attendances[1]->id,
            'new_date' => $user1Attendances[1]->date->toDateString(),
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'comment' => '休憩時間修正のため',
            'approval_status' => false,
        ]);

        ProposalBreak::factory()->create([
            'attendance_correction_request_id' => $correctionRequest->id,
            'break_in' => '12:10',
            'break_out' => '13:00',
        ]);

        // 直近3件目、承認済み：退勤時刻修正
        $correctionRequest = AttendanceCorrectionRequest::factory()->create([
            'user_id' => $user1->id,
            'attendance_record_id' => $user1Attendances[2]->id,
            'new_date' => $user1Attendances[2]->date->toDateString(),
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:10',
            'comment' => '退勤時間修正のため',
            'approval_status' => true,
        ]);

        ProposalBreak::factory()->create([
            'attendance_correction_request_id' => $correctionRequest->id,
        ]);

        // 承認済みの修正内容を正式な勤怠に反映
        $user1Attendances[2]->update([
            'clock_in' => '09:00',
            'clock_out' => '18:10',
        ]);

        // 以下、ユーザー2の修正申請作成
        // 直近1件目、承認待ち：出勤時刻修正
        $correctionRequest = AttendanceCorrectionRequest::factory()->create([
            'user_id' => $user2->id,
            'attendance_record_id' => $user2Attendances[0]->id,
            'new_date' => $user2Attendances[0]->date->toDateString(),
            'new_clock_in' => '09:10',
            'new_clock_out' => '18:00',
            'comment' => '出勤時間修正のため',
            'approval_status' => false,
        ]);

        ProposalBreak::factory()->create([
            'attendance_correction_request_id' => $correctionRequest->id,
        ]);

        // 直近2件目、承認済み：休憩時刻修正
        $correctionRequest = AttendanceCorrectionRequest::factory()->create([
            'user_id' => $user2->id,
            'attendance_record_id' => $user2Attendances[1]->id,
            'new_date' => $user2Attendances[1]->date->toDateString(),
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'comment' => '休憩時間修正のため',
            'approval_status' => true,
        ]);

        ProposalBreak::factory()->create([
            'attendance_correction_request_id' => $correctionRequest->id,
            'break_in' => '12:10',
            'break_out' => '13:00',
        ]);

        // 承認済みの修正内容を正式な休憩に反映
        $user2Attendances[1]->breaks()->firstOrFail()->update([
            'break_in' => '12:10',
            'break_out' => '13:00',
        ]);

        // 直近3件目、承認済み：退勤時刻修正
        $correctionRequest = AttendanceCorrectionRequest::factory()->create([
            'user_id' => $user2->id,
            'attendance_record_id' => $user2Attendances[2]->id,
            'new_date' => $user2Attendances[2]->date->toDateString(),
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:10',
            'comment' => '退勤時間修正のため',
            'approval_status' => true,
        ]);

        ProposalBreak::factory()->create([
            'attendance_correction_request_id' => $correctionRequest->id,
        ]);

        // 承認済みの修正内容を正式な勤怠に反映
        $user2Attendances[2]->update([
            'clock_in' => '09:00',
            'clock_out' => '18:10',
        ]);
    }
}
