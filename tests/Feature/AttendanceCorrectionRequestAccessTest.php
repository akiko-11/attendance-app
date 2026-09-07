<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionRequestAccessTest extends TestCase
{
    use RefreshDatabase;

    // 他人の勤怠には修正申請できない
    public function test_user_cannot_submit_correction_for_another_users_attendance(): void
    {
        // 一般ユーザーを2人作成
        $targetUser = User::factory()->create([
            'name' => '本人',
            'admin_status' => false,
        ]);

        $otherUser = User::factory()->create([
            'name' => '他人',
            'admin_status' => false,
        ]);

        // 相手の勤怠を作成
        $otherAttendanceRecord = $otherUser->attendanceRecords()->create([
            'date' => '2026-09-05',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
        ]);

        // 本人として、相手の勤怠IDへ正常な入力内容をPOST
        $response = $this->actingAs($targetUser)
            ->post("/attendance/{$otherAttendanceRecord->id}", [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['13:00'],
                'comment' => '打刻ミスのため',
            ]);

        // 他人の勤怠への申請が404で拒否されることを確認
        $response->assertNotFound();

        // 修正申請が保存されていないことを確認
        $this->assertSame(
            0,
            $otherAttendanceRecord->attendanceCorrectionRequests()->count()
        );
    }

    // 未ログインでは修正申請できない
    public function test_guest_cannot_submit_attendance_correction_request(): void
    {
        // 一般ユーザーと勤怠を作成
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-09-05',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
        ]);

        // actingAsを使わず、正常な入力内容をPOST
        $response = $this->post("/attendance/{$attendanceRecord->id}", [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['12:00'],
            'new_break_out' => ['13:00'],
            'comment' => '打刻ミスのため',
        ]);

        // ログイン画面へリダイレクトされることを確認
        $response->assertRedirect('/login');

        // 修正申請が保存されていないことを確認
        $this->assertSame(
            0,
            $attendanceRecord->attendanceCorrectionRequests()->count()
        );
    }
}
