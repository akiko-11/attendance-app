<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendancePendingDetailTest extends TestCase
{
    use RefreshDatabase;

    // 承認待ちの勤怠詳細には修正申請の内容が表示される
    public function test_pending_application_details_are_displayed(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        // 修正前の勤怠を作成
        $attendance = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-09-06',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '修正前の備考',
        ]);

        // 修正前の休憩を作成
        AttendanceBreak::factory()->create([
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        // 異なる出退勤・備考で承認待ちの修正申請を作成
        $application = AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendance->id,
            'new_date' => '2026-09-06',
            'new_clock_in' => '09:10:00',
            'new_clock_out' => '18:10:00',
            'comment' => '電車遅延のため',
            'approval_status' => false,
        ]);

        // 申請された休憩を作成
        $application->proposalBreaks()->create([
            'break_in' => '12:15:00',
            'break_out' => '13:15:00',
        ]);

        $response = $this->actingAs($user)
            ->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertViewIs('user.user-detail');

        // 修正申請の内容が表示されることを確認
        $response->assertSee('value="09:10"', false);
        $response->assertSee('value="18:10"', false);
        $response->assertSee('value="12:15"', false);
        $response->assertSee('value="13:15"', false);
        // 修正申請の備考がvalue属性に設定されている
        $response->assertSee('value="電車遅延のため"', false);

        // 修正前の勤怠内容が表示されないことを確認
        $response->assertDontSee('value="09:00"', false);
        $response->assertDontSee('value="18:00"', false);
        $response->assertDontSee('value="12:00"', false);
        $response->assertDontSee('value="13:00"', false);
        $response->assertDontSeeText('修正前の備考');

        // 承認待ちのため閲覧のみになっていることを確認
        $response->assertSeeText('承認待ちのため修正できません');
        $response->assertDontSee(
            'class="form__button--submit"',
            false
        );
    }
}
