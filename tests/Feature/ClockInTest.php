<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClockInTest extends TestCase
{
    use RefreshDatabase;

    // 出勤すると現在時刻が勤怠レコードに登録される
    public function test_user_can_clock_in(): void
    {
        Carbon::setTestNow('2026-08-27 09:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => null,
        ]);

        $attendance = AttendanceRecord::where('user_id', $user->id)->first();

        $this->assertSame(
            '2026-08-27',
            Carbon::parse($attendance->date)->toDateString()
        );

        Carbon::setTestNow();
    }

    // 出勤は1日に1回だけ登録される
    public function test_user_can_clock_in_only_once_per_day(): void
    {
        Carbon::setTestNow('2026-08-27 09:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        Carbon::setTestNow('2026-08-27 10:00:00');

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        $attendanceCount = AttendanceRecord::where('user_id', $user->id)
            ->whereDate('date', '2026-08-27')
            ->count();

        $this->assertSame(1, $attendanceCount);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
        ]);

        $this->assertDatabaseMissing('attendance_records', [
            'user_id' => $user->id,
            'clock_in' => '10:00:00',
        ]);

        Carbon::setTestNow();
    }
}
