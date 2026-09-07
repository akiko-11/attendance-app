<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    // 修正申請が承認待ちで保存され、元の勤怠は変更されない
    public function test_user_can_submit_attendance_correction_request(): void
    {
        // 一般ユーザーを作成
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        // 元の勤怠を作成
        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-09-05',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'comment' => '修正前の備考',
        ]);

        // 元の勤怠に紐づく休憩を作成
        $attendanceBreak = $attendanceRecord->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        // 本人として、修正したい内容をPOST
        $response = $this->actingAs($user)
            ->post("/attendance/{$attendanceRecord->id}", [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['12:30', ''],
                'new_break_out' => ['13:30', ''],
                'comment' => '打刻ミスのため',
            ]);

        // バリデーションエラーがなく、同じ勤怠詳細へ戻ることを確認
        $response->assertSessionHasNoErrors();
        $response->assertRedirect("/attendance/detail/{$attendanceRecord->id}");

        // 修正申請が承認待ちで保存されたことを確認
        $this->assertDatabaseHas('attendance_correction_requests', [
            'user_id' => $user->id,
            'attendance_record_id' => $attendanceRecord->id,
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'comment' => '打刻ミスのため',
            'approval_status' => false,
        ]);

        // 保存された修正申請を1件取得
        $application = $attendanceRecord->attendanceCorrectionRequests()
            ->sole();

        // 申請日付が元の勤怠日になっていることを確認
        $this->assertSame(
            '2026-09-05',
            $application->new_date->toDateString()
        );

        // 申請された休憩が保存されたことを確認
        $this->assertDatabaseHas('proposal_breaks', [
            'attendance_correction_request_id' => $application->id,
            'break_in' => '12:30',
            'break_out' => '13:30',
        ]);

        // 追加用の空欄は保存されず、申請休憩が1件であることを確認
        $this->assertSame(1, $application->proposalBreaks()->count());

        // 元の勤怠が変更されていないことを確認
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'comment' => '修正前の備考',
        ]);

        $this->assertSame(
            '2026-09-05',
            $attendanceRecord->refresh()->date->toDateString()
        );

        // 元の休憩が変更されていないことを確認
        $this->assertDatabaseHas('attendance_breaks', [
            'id' => $attendanceBreak->id,
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $this->assertSame(1, $attendanceRecord->breaks()->count());
    }

    // 退勤前・休憩中でも修正申請できる
    public function test_user_can_submit_correction_request_while_on_break(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        // 退勤時刻がnullの勤怠を作成
        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-09-05',
            'clock_in' => '08:00:00',
            'clock_out' => null,
        ]);

        // 休憩終了時刻がnullの休憩を作成
        $attendanceRecord->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => null,
        ]);

        // 退勤時刻・休憩終了時刻を空欄にしてPOST
        $response = $this->actingAs($user)
            ->post("/attendance/{$attendanceRecord->id}", [
                'new_clock_in' => '09:00',
                'new_clock_out' => '',
                'new_break_in' => ['12:30', ''],
                'new_break_out' => ['', ''],
                'comment' => '打刻ミスのため',
            ]);

        // バリデーションエラーがなく、詳細画面へ戻ることを確認
        $response->assertSessionHasNoErrors();
        $response->assertRedirect("/attendance/detail/{$attendanceRecord->id}");

        // 申請が承認待ちで保存され、退勤時刻がnullであることを確認
        $this->assertDatabaseHas('attendance_correction_requests', [
            'user_id' => $user->id,
            'attendance_record_id' => $attendanceRecord->id,
            'new_clock_in' => '09:00',
            'new_clock_out' => null,
            'comment' => '打刻ミスのため',
            'approval_status' => false,
        ]);

        // 保存された修正申請を1件取得
        $application = $attendanceRecord->attendanceCorrectionRequests()
            ->sole();

        // 申請休憩の開始時刻が保存され、終了時刻がnullであることを確認
        $this->assertDatabaseHas('proposal_breaks', [
            'attendance_correction_request_id' => $application->id,
            'break_in' => '12:30',
            'break_out' => null,
        ]);

        // 追加用の空欄は保存されていないことを確認
        $this->assertSame(1, $application->proposalBreaks()->count());
    }
}
