<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPendingApplicationListTest extends TestCase
{
    use RefreshDatabase;

    // 管理者が全一般ユーザーの承認待ち修正申請を確認できる
    public function test_admin_can_view_all_general_users_pending_applications(): void
    {
        Carbon::setTestNow('2026-09-02 10:00:00');

        $admin = User::factory()->create([
            'name' => '管理者',
            'admin_status' => true,
        ]);

        $user1 = User::factory()->create([
            'name' => 'ユーザー1',
            'admin_status' => false,
        ]);

        $user2 = User::factory()->create([
            'name' => 'ユーザー2',
            'admin_status' => false,
        ]);

        // 管理者の勤怠を作成
        $adminAttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $admin->id,
            'date' => '2026-07-31',
            'clock_in' => '08:01:00',
            'clock_out' => '17:01:00',
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

        // 管理者の修正申請を作成
        AttendanceCorrectionRequest::create([
            'user_id' => $admin->id,
            'attendance_record_id' => $adminAttendanceRecord->id,
            'new_date' => '2026-07-31',
            'new_clock_in' => '08:00:00',
            'new_clock_out' => '17:00:00',
            'comment' => '管理者固有の申請理由',
            'approval_status' => false,
        ]);

        // 各一般ユーザーの承認待ち修正申請を作成
        AttendanceCorrectionRequest::create([
            'user_id' => $user1->id,
            'attendance_record_id' => $user1AttendanceRecord->id,
            'new_date' => '2026-08-31',
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'comment' => '打刻ミスのため',
            'approval_status' => false,
        ]);

        AttendanceCorrectionRequest::create([
            'user_id' => $user2->id,
            'attendance_record_id' => $user2AttendanceRecord->id,
            'new_date' => '2026-09-01',
            'new_clock_in' => '09:10:00',
            'new_clock_out' => '18:10:00',
            'comment' => '遅延のため',
            'approval_status' => false,
        ]);

        $response = $this->actingAs($admin)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertViewIs('admin.admin-application-list');

        $response->assertViewHas(
            'applications',
            function ($applications) {
                // Viewへ渡された申請が2件(一般ユーザー)である
                // かつ、2件すべてが「承認待ち」である
                return $applications->count() === 2
                    && $applications->every(
                        fn ($application) => $application->approval_status
                            === '承認待ち'
                    );
            }
        );

        $response->assertSeeText($user1->name);
        $response->assertSeeText('2026/08/31');
        $response->assertSeeText('打刻ミスのため');

        $response->assertSeeText($user2->name);
        $response->assertSeeText('2026/09/01');
        $response->assertSeeText('遅延のため');

        $response->assertSeeText('2026/09/02');

        $response->assertDontSeeText($admin->name);
        $response->assertDontSeeText('2026/07/31');
        $response->assertDontSeeText('管理者固有の申請理由');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
