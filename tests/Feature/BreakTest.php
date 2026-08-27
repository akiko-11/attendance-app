<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreakTest extends TestCase
{
    use RefreshDatabase;

    // 休憩すると現在時刻が休憩レコードに登録される
    public function test_user_can_start_break(): void
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

        Carbon::setTestNow('2026-08-27 12:00:00');

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => null,
        ]);

        Carbon::setTestNow();
    }

    // 「休憩入」ボタンは1日に何回でも押下できる
    public function test_user_can_start_break_multiple_times_per_day(): void
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

        Carbon::setTestNow('2026-08-27 12:00:00');

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        Carbon::setTestNow('2026-08-27 13:00:00');

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_out',
        ]);

        Carbon::setTestNow('2026-08-27 15:00:00');

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        $attendanceBreakCount = AttendanceBreak::where('attendance_record_id', $attendance->id)
            ->count();

        $this->assertSame(2, $attendanceBreakCount);

        Carbon::setTestNow();
    }

    // 休憩戻の時刻が正しく登録される
    public function test_user_can_end_break(): void
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

        Carbon::setTestNow('2026-08-27 12:00:00');

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        Carbon::setTestNow('2026-08-27 13:00:00');

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'break_out',
        ]);

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        Carbon::setTestNow();
    }

    // 「休憩戻」ボタンは1日に何回でも押下できる
    public function test_user_can_end_break_multiple_times_per_day(): void
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

        Carbon::setTestNow('2026-08-27 12:00:00');

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        Carbon::setTestNow('2026-08-27 13:00:00');

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_out',
        ]);

        Carbon::setTestNow('2026-08-27 15:00:00');

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        Carbon::setTestNow('2026-08-27 16:00:00');

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_out',
        ]);

        $attendanceBreakCount = AttendanceBreak::where('attendance_record_id', $attendance->id)
            ->count();

        $this->assertSame(2, $attendanceBreakCount);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_record_id' => $attendance->id,
            'break_in' => '15:00:00',
            'break_out' => '16:00:00',
        ]);

        Carbon::setTestNow();
    }
}
