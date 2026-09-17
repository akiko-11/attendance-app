<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockOutTest extends TestCase
{
    use RefreshDatabase;

    // 退勤時刻が正しく登録されるテスト
    public function test_user_can_clock_out(): void
    {
        Carbon::setTestNow('2026-08-27 09:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        $attendance = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('date', '2026-08-27')
            ->first();

        Carbon::setTestNow('2026-08-27 18:00:00');

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_out',
        ]);

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->assertSame(
            '2026-08-27',
            Carbon::parse($attendance->date)->toDateString()
        );

        $pageResponse = $this->actingAs($user)->get('/attendance');

        $pageResponse->assertStatus(200);
        $pageResponse->assertSeeText('退勤済');
        $pageResponse->assertSeeText('お疲れ様でした。');
    }

    // 「退勤」ボタンは1日に1回だけ押下できる
    public function test_user_can_clock_out_only_once_per_day(): void
    {
        Carbon::setTestNow('2026-08-27 09:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        Carbon::setTestNow('2026-08-27 18:00:00');

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_out',
        ]);

        Carbon::setTestNow('2026-08-27 19:00:00');

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_out',
        ]);

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $this->assertDatabaseMissing('attendance_records', [
            'user_id' => $user->id,
            'clock_out' => '19:00:00',
        ]);
    }

    // 休憩中に「退勤」ボタンは押下できない
    public function test_user_cannot_clock_out_while_on_break(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()
            ->for($user)
            ->create([
                'date' => today()->toDateString(),
                'clock_in' => '09:00:00',
                'clock_out' => null,
            ]);

        $attendanceRecord->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => null,
        ]);

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_out',
            ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_out' => null,
        ]);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => null,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
