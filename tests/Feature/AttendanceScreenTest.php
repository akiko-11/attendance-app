<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceScreenTest extends TestCase
{
    use RefreshDatabase;

    // 現在の日時情報がUIと同じ形式で出力されている
    public function test_current_date_and_time_are_displayed_in_correct_format(): void
    {
        Carbon::setTestNow('2026-08-26 16:19:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('2026年8月26日(水)');
        $response->assertSee('16:19');

        Carbon::setTestNow();
    }

    // 勤務外の場合、勤怠ステータスが正しく表示される
    public function test_attendance_status_is_off_duty_when_no_attendance_exists(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('勤務外');
        $response->assertSee('出勤');
    }

    // 出勤中の場合、勤怠ステータスが正しく表示される
    public function test_attendance_status_is_working_when_clocked_in(): void
    {
        Carbon::setTestNow('2026-08-26 16:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-08-26',
            'clock_in' => '09:00:00',
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('出勤中');
        $response->assertSee('退勤');
        $response->assertSee('休憩入');

        Carbon::setTestNow();
    }

    // 休憩中の場合、勤怠ステータスが正しく表示される
    public function test_attendance_status_is_on_break(): void
    {
        Carbon::setTestNow('2026-08-26 16:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-08-26',
            'clock_in' => '09:00:00',
            'clock_out' => null,
        ]);

        AttendanceBreak::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩中');
        $response->assertSee('休憩戻');

        Carbon::setTestNow();
    }

    // 退勤済の場合、勤怠ステータスが正しく表示される
    public function test_attendance_status_is_clocked_out(): void
    {
        Carbon::setTestNow('2026-08-26 18:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-08-26',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('退勤済');
        $response->assertSee('お疲れ様でした。');

        Carbon::setTestNow();
    }

    // 退勤済のユーザーでは「出勤」ボタンが表示されない
    public function test_clock_in_button_is_not_displayed_when_clocked_out(): void
    {
        Carbon::setTestNow('2026-08-26 18:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-08-26',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertDontSee('value="clock_in"', false);

        Carbon::setTestNow();
    }
}
