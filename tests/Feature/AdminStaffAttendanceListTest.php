<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    // 選択したユーザーの勤怠が正しく表示される
    public function test_admin_can_view_selected_users_monthly_attendance(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $targetUser = User::factory()->create([
            'name' => '対象ユーザー',
            'admin_status' => false,
        ]);

        $otherUser = User::factory()->create([
            'name' => '別ユーザー',
            'admin_status' => false,
        ]);

        $targetAttendance = AttendanceRecord::factory()->create([
            'user_id' => $targetUser->id,
            'date' => '2026-08-03',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        AttendanceBreak::factory()->create([
            'attendance_record_id' => $targetAttendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $otherUser->id,
            'date' => '2026-08-03',
            'clock_in' => '07:30:00',
            'clock_out' => '16:30:00',
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/attendance/staff/{$targetUser->id}?date=2026-08");

        $response->assertStatus(200);
        $response->assertSeeText($targetUser->name);
        $response->assertSeeText('2026/08');
        $response->assertSeeText('08/03(月)');
        $response->assertSeeText('09:00');
        $response->assertSeeText('18:00');
        $response->assertSeeText('1:00');
        $response->assertSeeText('8:00');
        $response->assertDontSeeText('07:30');
        $response->assertDontSeeText($otherUser->name);
    }

    // 未記録の項目が空白になる
    public function test_unrecorded_attendance_fields_are_blank(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $targetUser = User::factory()->create([
            'name' => '対象ユーザー',
            'admin_status' => false,
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $targetUser->id,
            'date' => '2026-08-04',
            'clock_in' => '09:00:00',
            'clock_out' => null,
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/attendance/staff/{$targetUser->id}?date=2026-08");

        $response->assertViewHas(
            'formattedAttendanceRecords',
            function ($records) {
                $attendance = collect($records)
                    ->firstWhere('date', '08/04(火)');

                return $attendance !== null
                    && $attendance['clock_in'] === '09:00'
                    && $attendance['clock_out'] === ''
                    && $attendance['total_break_time'] === ''
                    && $attendance['total_time'] === '';
            }
        );
    }

    // 一般ユーザーはアクセスできない
    public function test_general_user_cannot_access_admin_staff_attendance_list(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)
            ->get("/admin/attendance/staff/{$user->id}");

        $response->assertForbidden();
    }

    // 未ログイン時はログイン画面へ遷移する
    public function test_guest_is_redirected_to_login_from_admin_staff_attendance_list(): void
    {
        $targetUser = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->get(
            "/admin/attendance/staff/{$targetUser->id}"
        );

        $response->assertRedirect('/login');
    }
}
