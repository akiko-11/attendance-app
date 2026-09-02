<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApplicationListAccessTest extends TestCase
{
    use RefreshDatabase;

    // 一般ユーザーはログインしたユーザー本人の申請だけが表示される
    public function test_general_user_can_view_only_their_own_applications(): void
    {
        $user1 = User::factory()->create([
            'name' => 'ユーザー1',
            'admin_status' => false,
        ]);

        $user2 = User::factory()->create([
            'name' => 'ユーザー2',
            'admin_status' => false,
        ]);

        // 各一般ユーザーの勤怠を作成
        $user1AttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user1->id,
            'date' => '2026-08-31',
            'clock_in' => '09:00:00',
            'clock_out' => '18:01:00',
        ]);

        $user2AttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user2->id,
            'date' => '2026-09-01',
            'clock_in' => '09:09:00',
            'clock_out' => '18:09:00',
        ]);

        // 各一般ユーザーの修正申請を作成
        AttendanceCorrectionRequest::create([
            'user_id' => $user1->id,
            'attendance_record_id' => $user1AttendanceRecord->id,
            'new_date' => '2026-08-31',
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'comment' => '打刻ミスのため',
            'approval_status' => true,
        ]);

        AttendanceCorrectionRequest::create([
            'user_id' => $user2->id,
            'attendance_record_id' => $user2AttendanceRecord->id,
            'new_date' => '2026-08-30',
            'new_clock_in' => '09:10:00',
            'new_clock_out' => '18:10:00',
            'comment' => '遅延のため',
            'approval_status' => true,
        ]);

        $response = $this->actingAs($user1)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertViewIs('user.user-application-list');

        $response->assertSeeText($user1->name);
        $response->assertSeeText('2026/08/31');
        $response->assertSeeText('打刻ミスのため');

        $response->assertDontSeeText($user2->name);
        $response->assertDontSeeText('2026/08/30');
        $response->assertDontSeeText('遅延のため');
    }

    // 未ログイン状態で同じURLへアクセスし、ログイン画面へリダイレクトされる
    public function test_guest_is_redirected_to_login_from_application_list(): void
    {
        $response = $this->get('/stamp_correction_request/list');

        $response->assertRedirect('/login');
    }
}
