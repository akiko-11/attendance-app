<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApplicationApprovalAccessTest extends TestCase
{
    use RefreshDatabase;

    // 一般ユーザーは管理者用の修正申請詳細を閲覧できない
    public function test_general_user_cannot_view_admin_application_detail(): void
    {
        // 一般ユーザー・勤怠・未承認の修正申請を作成
        $user = User::factory()->create([
            'name' => 'ユーザー',
            'admin_status' => false,
        ]);

        $userAttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '修正前の備考',
        ]);

        $application = AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $userAttendanceRecord->id,
            'new_date' => '2026-09-05',
            'new_clock_in' => '09:10:00',
            'new_clock_out' => '18:10:00',
            'comment' => '打刻ミスのため',
            'approval_status' => false,
        ]);

        // 一般ユーザーでログインし、実在する申請IDの詳細URLへGETする
        $response = $this->actingAs($user)
            ->get("/stamp_correction_request/approve/{$application->id}");

        $response->assertForbidden();
    }

    // 一般ユーザーは修正申請を承認できない
    public function test_general_user_cannot_approve_application(): void
    {
        // 一般ユーザー・勤怠・未承認の修正申請を作成
        $user = User::factory()->create([
            'name' => 'ユーザー',
            'admin_status' => false,
        ]);

        $userAttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '修正前の備考',
        ]);

        $application = AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $userAttendanceRecord->id,
            'new_date' => '2026-09-05',
            'new_clock_in' => '09:10:00',
            'new_clock_out' => '18:10:00',
            'comment' => '打刻ミスのため',
            'approval_status' => false,
        ]);

        // 一般ユーザーとして承認URLへPOSTする
        $response = $this->actingAs($user)
            ->post("/stamp_correction_request/approve/{$application->id}");

        // 403であることを確認
        $response->assertForbidden();

        // 申請が未承認のままで、勤怠が変更されていないことを確認
        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $application->id,
            'approval_status' => false,
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $userAttendanceRecord->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '修正前の備考',
        ]);

        $this->assertSame(
            '2026-09-04',
            $userAttendanceRecord->refresh()->date->toDateString()
        );
    }

    // 未ログインでは修正申請詳細からログイン画面へ遷移する
    public function test_guest_is_redirected_to_login_from_application_detail(): void
    {
        // 一般ユーザー・勤怠・未承認の修正申請を作成
        $user = User::factory()->create([
            'name' => 'ユーザー',
            'admin_status' => false,
        ]);

        $userAttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '修正前の備考',
        ]);

        $application = AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $userAttendanceRecord->id,
            'new_date' => '2026-09-05',
            'new_clock_in' => '09:10:00',
            'new_clock_out' => '18:10:00',
            'comment' => '打刻ミスのため',
            'approval_status' => false,
        ]);

        // 未ログインの状態で、詳細URLへGETする
        $response = $this->get("/stamp_correction_request/approve/{$application->id}");

        // loginへリダイレクトされることを確認
        $response->assertRedirect('/login');
    }

    // 未ログインでは承認処理からログイン画面へ遷移する
    public function test_guest_is_redirected_to_login_when_approving_application(): void
    {
        // 一般ユーザー・勤怠・未承認の申請を作成
        $user = User::factory()->create([
            'name' => 'ユーザー',
            'admin_status' => false,
        ]);

        $userAttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '修正前の備考',
        ]);

        $application = AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $userAttendanceRecord->id,
            'new_date' => '2026-09-05',
            'new_clock_in' => '09:10:00',
            'new_clock_out' => '18:10:00',
            'comment' => '打刻ミスのため',
            'approval_status' => false,
        ]);

        // 未ログインの状態で、承認URLへPOST
        $response = $this->post(
            "/stamp_correction_request/approve/{$application->id}"
        );

        $response->assertRedirect('/login');

        // 未承認のままであることを確認
        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $application->id,
            'approval_status' => false,
        ]);

        // 元の勤怠が変更されていないことを確認
        $this->assertDatabaseHas('attendance_records', [
            'id' => $userAttendanceRecord->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '修正前の備考',
        ]);

        $this->assertSame(
            '2026-09-04',
            $userAttendanceRecord->refresh()->date->toDateString()
        );
    }
}
