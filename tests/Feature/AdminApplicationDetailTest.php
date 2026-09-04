<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApplicationDetailTest extends TestCase
{
    use RefreshDatabase;

    // 修正申請の詳細内容が正しく表示されている
    public function test_correction_request_details_are_displayed_correctly(): void
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

        // 一般ユーザーの勤怠を作成
        $userAttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '修正前の備考',
        ]);

        AttendanceBreak::factory()->create([
            'attendance_record_id' => $userAttendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        // 承認待ちの修正申請を作成
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

        // 管理者として申請詳細URLへGETする
        $response = $this->actingAs($admin)
            ->get("/stamp_correction_request/approve/{$application->id}");

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-application-detail');
        // 申請者・日付・出退勤・休憩・備考を確認
        $response->assertSee('value="ユーザー"', false);
        $response->assertSee('value="2026年"', false);
        $response->assertSee('value="9月5日"', false);
        $response->assertSee('value="09:10"', false);
        $response->assertSee('value="18:10"', false);
        $response->assertSee('value="12:15"', false);
        $response->assertSee('value="13:15"', false);

        // 承認ボタンが表示されていることを確認
        $response->assertSeeText('打刻ミスのため');
        $response->assertSeeText('承認');
        $response->assertDontSeeText('承認済み');
    }
}
