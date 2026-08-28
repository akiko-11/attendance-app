<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    // 勤怠一覧画面に現在の月が表示される
    public function test_current_month_is_displayed(): void
    {
        Carbon::setTestNow('2026-08-28 10:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        // 勤怠一覧画面が正常に表示される
        $response->assertStatus(200);

        // 現在の年月が表示される
        $response->assertSee('2026/08');

        Carbon::setTestNow();
    }

    // 自分の勤怠情報がすべて表示される
    public function test_user_can_see_all_own_attendance_records(): void
    {
        Carbon::setTestNow('2026-08-28 10:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $otherUser = User::factory()->create([
            'admin_status' => false,
        ]);

        // 自分の勤怠1件目
        $user->attendanceRecords()->create([
            'date' => '2026-08-05',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 自分の勤怠2件目
        $user->attendanceRecords()->create([
            'date' => '2026-08-20',
            'clock_in' => '09:10:00',
            'clock_out' => '18:10:00',
        ]);

        // 他ユーザーの勤怠
        $otherUser->attendanceRecords()->create([
            'date' => '2026-08-10',
            'clock_in' => '07:15:00',
            'clock_out' => '16:45:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        // 自分の勤怠が表示されていることを確認
        $response->assertSee('08/05(水)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertSee('08/20(木)');
        $response->assertSee('09:10');
        $response->assertSee('18:10');

        // 他ユーザーの勤怠時刻は表示されない
        $response->assertDontSee('07:15');
        $response->assertDontSee('16:45');

        Carbon::setTestNow();
    }

    // 「前月」を押下した時に前月の情報が表示される
    public function test_previous_month_attendance_is_displayed(): void
    {
        Carbon::setTestNow('2026-08-28 10:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        // 前月の勤怠を準備
        $user->attendanceRecords()->create([
            'date' => '2026-07-28',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 前月を表示
        $response = $this->actingAs($user)
            ->get('/attendance/list?date=2026-07');

        $response->assertStatus(200);

        // 前月の年月が表示される
        $response->assertSee('2026/07');

        // 前月の勤怠情報が表示される
        $response->assertSee('07/28(火)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        Carbon::setTestNow();
    }

    // 「翌月」を押下した時に翌月の情報が表示される
    public function test_next_month_attendance_is_displayed(): void
    {
        Carbon::setTestNow('2026-08-28 10:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        // 翌月の勤怠を準備
        $user->attendanceRecords()->create([
            'date' => '2026-09-28',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 翌月を表示
        $response = $this->actingAs($user)
            ->get('/attendance/list?date=2026-09');

        $response->assertStatus(200);

        // 翌月の年月が表示される
        $response->assertSee('2026/09');

        // 翌月の勤怠情報が表示される
        $response->assertSee('09/28(月)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        Carbon::setTestNow();
    }
}
