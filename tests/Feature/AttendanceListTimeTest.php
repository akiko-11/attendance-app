<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTimeTest extends TestCase
{
    use RefreshDatabase;

    // 出勤時刻が勤怠一覧画面で確認できる
    public function test_clock_in_time_is_displayed_on_attendance_list(): void
    {
        // 09:00に出勤
        Carbon::setTestNow('2026-08-28 09:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        // 出勤処理
        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 勤怠一覧画面を表示
        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        // 出勤時刻が正確に表示される
        $response->assertSee('08/28(金)');
        $response->assertSee('09:00');

        Carbon::setTestNow();
    }

    // 退勤時刻が勤怠一覧画面で確認できる
    public function test_clock_out_time_is_displayed_on_attendance_list(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        // 09:00に出勤
        Carbon::setTestNow('2026-08-28 09:00:00');

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 18:00に退勤
        Carbon::setTestNow('2026-08-28 18:00:00');

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_out',
            ]);

        // 勤怠一覧画面を表示
        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        // 退勤した日付と退勤時刻が表示される
        $response->assertSee('08/28(金)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        Carbon::setTestNow();
    }

    // 休憩時間が勤怠一覧画面で確認できる
    public function test_break_time_is_displayed_on_attendance_list(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        // 09:00に出勤
        Carbon::setTestNow('2026-08-28 09:00:00');

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'clock_in',
            ]);

        // 12:00に休憩入
        Carbon::setTestNow('2026-08-28 12:00:00');

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_in',
            ]);

        // 13:00に休憩戻
        Carbon::setTestNow('2026-08-28 13:00:00');

        $this->actingAs($user)
            ->post('/attendance', [
                'action' => 'break_out',
            ]);

        // 勤怠一覧画面を表示
        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $response->assertStatus(200);

        // 休憩した日付と休憩時間が表示される
        $response->assertSee('08/28(金)');
        $response->assertSee('1:00');

        Carbon::setTestNow();
    }
}
