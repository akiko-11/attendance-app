<?php

namespace Tests\Feature\Attendance;

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

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
