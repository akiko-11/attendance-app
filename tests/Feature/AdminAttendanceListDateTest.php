<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListDateTest extends TestCase
{
    use RefreshDatabase;

    // 初期表示で現在の日付が表示される
    public function test_current_date_is_displayed_by_default(): void
    {
        Carbon::setTestNow('2026-08-31 10:00:00');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list');

        $response->assertStatus(200);

        // 現在の日付
        $response->assertSee('2026/08/31');

        Carbon::setTestNow();
    }

    // 前日を指定すると前日の勤怠情報が表示される
    public function test_previous_day_attendance_is_displayed(): void
    {
        Carbon::setTestNow('2026-08-31 10:00:00');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        // 前日のユーザー・勤怠を準備
        $user1 = User::factory()->create([
            'name' => 'ユーザー1',
            'admin_status' => false,
        ]);

        $user1AttendanceRecord = AttendanceRecord::create([
            'user_id' => $user1->id,
            'date' => '2026-08-30',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        AttendanceBreak::create([
            'attendance_record_id' => $user1AttendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-08-30');

        $response->assertStatus(200);
        $response->assertSee('2026/08/30');
        // 以下、名前、出勤、退勤、休憩、合計を表示
        $response->assertSee('ユーザー1');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');

        Carbon::setTestNow();
    }

    // 翌日を指定すると翌日の勤怠情報が表示される
    public function test_next_day_attendance_is_displayed(): void
    {
        Carbon::setTestNow('2026-08-31 10:00:00');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        // 翌日のユーザー・勤怠を準備
        $user1 = User::factory()->create([
            'name' => 'ユーザー1',
            'admin_status' => false,
        ]);

        $user1AttendanceRecord = AttendanceRecord::create([
            'user_id' => $user1->id,
            'date' => '2026-09-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        AttendanceBreak::create([
            'attendance_record_id' => $user1AttendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-09-01');

        $response->assertStatus(200);
        $response->assertSee('2026/09/01');

        // 以下、名前、出勤、退勤、休憩、合計を表示
        $response->assertSee('ユーザー1');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');

        Carbon::setTestNow();
    }

    // 前日・翌日リンクに正しい日付が設定される
    public function test_previous_and_next_day_links_are_correct(): void
    {
        Carbon::setTestNow('2026-08-31 10:00:00');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list');

        $response->assertStatus(200);

        // 前日リンクを確認
        $response->assertSee('href="?date=2026-08-30"', false);

        // 翌日リンクを確認
        $response->assertSee('href="?date=2026-09-01"', false);

        Carbon::setTestNow();
    }
}
