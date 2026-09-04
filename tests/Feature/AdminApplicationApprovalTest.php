<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApplicationApprovalTest extends TestCase
{
    use RefreshDatabase;

    // 修正申請の承認処理が正しく行われる
    public function test_admin_can_approve_correction_request_correctly(): void
    {
        // 管理者と一般ユーザーを作成
        $admin = User::factory()->create([
            'name' => '管理者',
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'ユーザー',
            'admin_status' => false,
        ]);

        // 一般ユーザーの元の勤怠と休憩を作成
        $userAttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '修正前の備考',
        ]);

        $userAttendanceBreak = AttendanceBreak::factory()->create([
            'attendance_record_id' => $userAttendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        // その勤怠に対する未承認の修正申請を作成
        $application = AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $userAttendanceRecord->id,
            'new_date' => '2026-09-05',
            'new_clock_in' => '09:10:00',
            'new_clock_out' => '18:10:00',
            'comment' => '打刻ミスのため',
            'approval_status' => false,
        ]);

        // 申請された休憩を作成
        $application->proposalBreaks()->create([
            'break_in' => '12:15:00',
            'break_out' => '13:15:00',
        ]);

        // 管理者として承認URLへPOSTする
        $url = "/stamp_correction_request/approve/{$application->id}";

        $response = $this->actingAs($admin)->post($url);

        // 申請詳細画面へリダイレクトされることを確認
        $response->assertRedirect($url);

        // 元の勤怠が申請内容で更新されたことを確認
        $this->assertDatabaseHas('attendance_records', [
            'id' => $userAttendanceRecord->id,
            'user_id' => $user->id,
            'clock_in' => '09:10:00',
            'clock_out' => '18:10:00',
            'comment' => '打刻ミスのため',
        ]);

        // 更新後の勤怠をDBから再取得し、日付を確認
        $this->assertSame(
            '2026-09-05',
            $userAttendanceRecord->refresh()->date->toDateString()
        );

        // 対象の勤怠に、申請された休憩が反映されたことを確認
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_record_id' => $userAttendanceRecord->id,
            'break_in' => '12:15:00',
            'break_out' => '13:15:00',
        ]);

        // 変更前の休憩が残っていないことを確認
        $this->assertDatabaseMissing('attendance_breaks', [
            'id' => $userAttendanceBreak->id,
        ]);

        // 対象の勤怠の休憩が1件であることを確認
        $this->assertSame(1, $userAttendanceRecord->breaks()->count());

        // 修正申請が承認済みになったことを確認
        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $application->id,
            'approval_status' => true,
        ]);

        // 承認後の詳細画面を再取得
        $detailResponse = $this->get($url);

        $detailResponse->assertStatus(200);
        $detailResponse->assertSeeText('承認済み');

        // 承認ボタンが表示されないことを確認
        $detailResponse->assertDontSee(
            'class="applied-form__button--submit"',
            false
        );
    }
}
