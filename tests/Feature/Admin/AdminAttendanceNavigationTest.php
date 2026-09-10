<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceNavigationTest extends TestCase
{
    use RefreshDatabase;

    // 日次勤怠一覧から対象の勤怠詳細へ遷移できる
    public function test_admin_can_navigate_from_daily_attendance_list_to_detail(): void
    {
        Carbon::setTestNow('2026-08-31 19:00:00');

        $admin = $this->createUser([
            'admin_status' => true,
        ]);

        [$user, $attendance] = $this->createUserWithAttendance([
            'name' => '対象ユーザー',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-08-31');

        $response->assertStatus(200);
        $response->assertSee(
            'href="'.url("/attendance/{$attendance->id}").'"',
            false
        );

        $linkResponse = $this->actingAs($admin)
            ->get("/attendance/{$attendance->id}");

        $linkResponse->assertRedirect(
            "/admin/attendance/{$attendance->id}"
        );

        $detailResponse = $this->actingAs($admin)
            ->get("/admin/attendance/{$attendance->id}");

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee($user->name);
    }

    private function createUser(array $overrides = []): User
    {
        return User::factory()->create(
            array_merge([
                'admin_status' => false,
            ], $overrides)
        );
    }

    private function createUserWithAttendance(
        array $userOverrides = [],
        array $attendanceOverrides = []
    ): array {
        $user = $this->createUser($userOverrides);

        $attendance = AttendanceRecord::factory()->create(
            array_merge([
                'user_id' => $user->id,
                'date' => '2026-08-31',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '通常勤務',
            ], $attendanceOverrides)
        );

        AttendanceBreak::factory()->create([
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        return [$user, $attendance];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
