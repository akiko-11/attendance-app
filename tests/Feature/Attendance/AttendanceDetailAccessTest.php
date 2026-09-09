<?php

namespace Tests\Feature\Attendance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailAccessTest extends TestCase
{
    use RefreshDatabase;

    // 他人の勤怠詳細は閲覧できない
    public function test_user_cannot_view_another_users_attendance_detail(): void
    {
        $user = User::factory()->create([
            'name' => '本人',
            'admin_status' => false,
        ]);

        $otherUser = User::factory()->create([
            'name' => '他人',
            'admin_status' => false,
        ]);

        $otherAttendance = $otherUser->attendanceRecords()->create([
            'date' => '2026-09-05',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
        ]);

        // 本人として、他人の勤怠詳細へアクセス
        $response = $this->actingAs($user)
            ->get("/attendance/detail/{$otherAttendance->id}");

        $response->assertNotFound();
    }

    // 未ログインユーザーは勤怠詳細からログイン画面へリダイレクトされる
    public function test_guest_is_redirected_to_login_from_attendance_detail(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendance = $user->attendanceRecords()->create([
            'date' => '2026-09-05',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // ログインせず勤怠詳細へアクセス
        $response = $this->get(
            "/attendance/detail/{$attendance->id}"
        );

        $response->assertRedirect('/login');
    }
}
