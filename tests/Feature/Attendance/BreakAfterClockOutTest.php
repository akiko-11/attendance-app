<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BreakAfterClockOutTest extends TestCase
{
    use RefreshDatabase;

    // 退勤後に休憩開始をPOSTしても、休憩は登録されない
    public function test_user_cannot_start_break_after_clock_out(): void
    {
        Carbon::setTestNow('2026-09-01 19:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendance = $user->attendanceRecords()->create([
            'date' => '2026-09-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        $response->assertRedirect('/attendance');

        $this->assertSame(0, $attendance->breaks()->count());

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendance->id,
            'clock_out' => '18:00:00',
        ]);
    }

    // 退勤済みの場合は休憩終了できない
    public function test_user_cannot_end_break_after_clocking_out(): void
    {
        $user = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()
            ->for($user)
            ->create([
                'date' => today()->toDateString(),
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ]);

        $attendanceRecord->breaks()->create([
            'break_in' => '12:00:00',
            'break_out' => null,
        ]);

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
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
