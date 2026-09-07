<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    // 出勤時刻が退勤時刻より後の場合、エラーになる
    public function test_clock_in_after_clock_out_is_rejected(): void
    {
        // 一般ユーザーと元の勤怠を作成
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-09-05',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
        ]);

        // 出勤18:00・退勤09:00で修正申請をPOST
        $response = $this->actingAs($user)
            ->from("/attendance/detail/{$attendanceRecord->id}")
            ->post("/attendance/{$attendanceRecord->id}", [
                'new_clock_in' => '18:00',
                'new_clock_out' => '09:00',
                'new_break_in' => [''],
                'new_break_out' => [''],
                'comment' => '打刻ミスのため',
            ]);

        // 入力元の勤怠詳細へ戻ることを確認
        $response->assertRedirect("/attendance/detail/{$attendanceRecord->id}");

        // 対象項目に指定のエラーメッセージがあることを確認
        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);

        // 修正申請が保存されていないことを確認
        $this->assertSame(
            0,
            $attendanceRecord->attendanceCorrectionRequests()->count()
        );
    }

    // 休憩開始時刻が出勤時刻より前の場合、エラーになる
    public function test_break_start_before_clock_in_is_rejected(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-09-05',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
        ]);

        // 出勤09:00・退勤18:00、休憩08:30〜10:00でPOST
        $response = $this->actingAs($user)
            ->from("/attendance/detail/{$attendanceRecord->id}")
            ->post("/attendance/{$attendanceRecord->id}", [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['08:30'],
                'new_break_out' => ['10:00'],
                'comment' => '打刻ミスのため',
            ]);

        // 休憩開始時刻に指定のエラーメッセージがあることを確認
        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
        ]);

        // 修正申請が保存されていないことを確認
        $this->assertSame(
            0,
            $attendanceRecord->attendanceCorrectionRequests()->count()
        );
    }

    // 休憩開始時刻が退勤時刻より後の場合、エラーになる
    public function test_break_start_after_clock_out_is_rejected(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-09-05',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
        ]);

        // 出勤09:00・退勤18:00、休憩18:30でPOST
        $response = $this->actingAs($user)
            ->from("/attendance/detail/{$attendanceRecord->id}")
            ->post("/attendance/{$attendanceRecord->id}", [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['18:30'],
                'new_break_out' => [''],
                'comment' => '打刻ミスのため',
            ]);

        // 休憩開始時刻に指定のエラーメッセージがあることを確認
        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
        ]);

        // 修正申請が保存されていないことを確認
        $this->assertSame(
            0,
            $attendanceRecord->attendanceCorrectionRequests()->count()
        );
    }

    // 休憩終了時刻が退勤時刻より後の場合、エラーになる
    public function test_break_end_after_clock_out_is_rejected(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-09-05',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
        ]);

        // 出勤09:00・退勤18:00、休憩17:30〜18:30でPOST
        $response = $this->actingAs($user)
            ->from("/attendance/detail/{$attendanceRecord->id}")
            ->post("/attendance/{$attendanceRecord->id}", [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['17:30'],
                'new_break_out' => ['18:30'],
                'comment' => '打刻ミスのため',
            ]);

        // 休憩終了時刻に指定のエラーメッセージがあることを確認
        $response->assertSessionHasErrors([
            'new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);

        // 修正申請が保存されていないことを確認
        $this->assertSame(
            0,
            $attendanceRecord->attendanceCorrectionRequests()->count()
        );
    }

    // 備考が未入力の場合、エラーになる
    public function test_comment_is_required(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-09-05',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
        ]);

        // 備考だけ空欄でPOST
        $response = $this->actingAs($user)
            ->from("/attendance/detail/{$attendanceRecord->id}")
            ->post("/attendance/{$attendanceRecord->id}", [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['13:00'],
                'comment' => '',
            ]);

        // 備考に指定のエラーメッセージがあることを確認
        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);

        // 修正申請が保存されていないことを確認
        $this->assertSame(
            0,
            $attendanceRecord->attendanceCorrectionRequests()->count()
        );
    }
}
