<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceStampReflectionTest extends TestCase
{
    use RefreshDatabase;

    // 一般ユーザーの実打刻が管理者の勤怠一覧・詳細に反映される
    public function test_user_stamps_are_reflected_in_admin_attendance_screens(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'admin_status' => false,
        ]);

        // 出勤
        Carbon::setTestNow('2026-08-31 09:00:00');

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ])->assertRedirect('/attendance');

        // 休憩開始
        Carbon::setTestNow('2026-08-31 12:00:00');

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ])->assertRedirect('/attendance');

        // 休憩終了
        Carbon::setTestNow('2026-08-31 13:00:00');

        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_out',
        ])->assertRedirect('/attendance');

        // 退勤
        Carbon::setTestNow('2026-08-31 18:00:00');

        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_out',
        ])->assertRedirect('/attendance');

        $attendance = $user->attendanceRecords()
            ->whereDate('date', '2026-08-31')
            ->sole();

        // 管理者の勤怠一覧に実打刻内容が反映される
        $listResponse = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-08-31');

        $listResponse->assertStatus(200);
        $listResponse->assertSee('テストユーザー');
        $listResponse->assertSee('09:00');
        $listResponse->assertSee('18:00');
        $listResponse->assertSee('1:00');
        $listResponse->assertSee('8:00');

        // 管理者の勤怠詳細にも実打刻内容が反映される
        $detailResponse = $this->actingAs($admin)
            ->get("/admin/attendance/{$attendance->id}");

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('テストユーザー');
        $detailResponse->assertSee('09:00');
        $detailResponse->assertSee('18:00');
        $detailResponse->assertSee('12:00');
        $detailResponse->assertSee('13:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
