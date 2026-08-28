<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationListTest extends TestCase
{
    use RefreshDatabase;

    // 自分の承認待ち申請が表示される
    public function test_pending_application_is_displayed(): void
    {
        Carbon::setTestNow('2026-08-28 10:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        // 修正対象の勤怠を準備
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-08-26',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 承認待ちの修正申請を準備
        AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendanceRecord->id,
            'new_date' => '2026-08-27',
            'new_clock_in' => '09:10:00',
            'new_clock_out' => '18:10:00',
            'comment' => '出勤時間修正のため',
            'approval_status' => false,
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);

        // 承認待ち申請の内容が表示される
        $response->assertSee('承認待ち');
        $response->assertSee($user->name);
        $response->assertSee('2026/08/26');
        $response->assertSee('出勤時間修正のため');
        $response->assertSee('2026/08/28');

        Carbon::setTestNow();
    }

    // 自分の承認済み申請が表示される
    public function test_approved_application_is_displayed(): void
    {
        Carbon::setTestNow('2026-08-28 10:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        // 修正対象の勤怠を準備
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-08-27',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 承認済みの修正申請を準備
        AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendanceRecord->id,
            'new_date' => '2026-08-27',
            'new_clock_in' => '08:50:00',
            'new_clock_out' => '17:50:00',
            'comment' => '出勤時間修正のため',
            'approval_status' => true,
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);

        // 承認済み申請の内容が表示される
        $response->assertSee('承認済み');
        $response->assertSee($user->name);
        $response->assertSee('2026/08/27');
        $response->assertSee('出勤時間修正のため');
        $response->assertSee('2026/08/28');

        Carbon::setTestNow();
    }

    // 他ユーザーの申請が表示されない
    public function test_other_users_applications_are_not_displayed(): void
    {
        Carbon::setTestNow('2026-08-28 10:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $otherUser = User::factory()->create([
            'admin_status' => false,
        ]);

        // 自分の勤怠を準備
        $attendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-08-27',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 他ユーザーの勤怠を準備
        $otherAttendanceRecord = AttendanceRecord::create([
            'user_id' => $otherUser->id,
            'date' => '2026-08-27',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
        ]);

        // 自分の申請
        AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendanceRecord->id,
            'new_date' => '2026-08-27',
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'comment' => '自分の申請',
            'approval_status' => true,
        ]);

        // 他ユーザーの申請
        AttendanceCorrectionRequest::create([
            'user_id' => $otherUser->id,
            'attendance_record_id' => $otherAttendanceRecord->id,
            'new_date' => '2026-08-27',
            'new_clock_in' => '08:00:00',
            'new_clock_out' => '17:00:00',
            'comment' => '他ユーザーの申請',
            'approval_status' => true,
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);

        // 自分の申請は表示される
        $response->assertSee('自分の申請');

        // 他ユーザーの申請は表示されない
        $response->assertDontSee('他ユーザーの申請');

        Carbon::setTestNow();
    }
}
