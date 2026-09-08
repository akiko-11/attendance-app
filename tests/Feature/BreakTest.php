<?php

namespace Tests\Feature;

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
        [$user, $attendance] = $this->createClockedInUser();

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
    }

    // 「休憩入」ボタンは1日に何回でも押下できる
    public function test_user_can_start_break_multiple_times_per_day(): void
    {
        [$user, $attendance] = $this->createClockedInUser();

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

        $this->assertSame(2, $attendance->breaks()->count());
    }

    // 休憩戻の時刻が正しく登録される
    public function test_user_can_end_break(): void
    {
        [$user, $attendance] = $this->createClockedInUser();

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
    }

    // 「休憩戻」ボタンは1日に何回でも押下できる
    public function test_user_can_end_break_multiple_times_per_day(): void
    {
        [$user, $attendance] = $this->createClockedInUser();

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

        $this->assertSame(2, $attendance->breaks()->count());

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
    }

    // 休憩中に再度休憩開始しても、休憩レコードは増えない
    public function test_user_cannot_start_break_while_on_break(): void
    {
        [$user, $attendance] = $this->createClockedInUser('2026-09-01');

        $break = $attendance->breaks()->create([
            'break_in' => '11:00:00',
            'break_out' => null,
        ]);

        Carbon::setTestNow('2026-09-01 12:00:00');

        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        $response->assertRedirect('/attendance');

        $this->assertSame(1, $attendance->breaks()->count());

        $this->assertDatabaseHas('attendance_breaks', [
            'id' => $break->id,
            'break_in' => '11:00:00',
            'break_out' => null,
        ]);
    }

    // 一般ユーザーを作成し、指定日の9時に出勤する
    private function createClockedInUser(
        string $date = '2026-08-27'
    ): array {
        Carbon::setTestNow($date.' 09:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ])->assertRedirect('/attendance');

        $attendance = $user->attendanceRecords()
            ->whereDate('date', $date)
            ->sole();

        return [$user, $attendance];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
