<?php

namespace Tests\Feature;

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

        Carbon::setTestNow();
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

        Carbon::setTestNow();
    }
}
