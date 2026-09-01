<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffAttendanceListDateTest extends TestCase
{
    use RefreshDatabase;

    // 初期表示が現在月になる
    public function test_initial_display_shows_current_month(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $targetUser = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($admin)
            ->get("/admin/attendance/staff/{$targetUser->id}");

        $response->assertStatus(200);
        $response->assertSeeText('2026/08');
        $response->assertSee('?date=2026-07');
        $response->assertSee('?date=2026-09');
    }

    // 「前月」を押下した時に表示月の前月の情報が表示される
    public function test_previous_month_displays_previous_month_attendance(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $targetUser = User::factory()->create([
            'admin_status' => false,
        ]);

        // 対象ユーザーの7月勤怠
        AttendanceRecord::factory()->create([
            'user_id' => $targetUser->id,
            'date' => '2026-07-06',
            'clock_in' => '08:45:00',
            'clock_out' => '17:45:00',
        ]);

        // 対象ユーザーの8月勤怠
        AttendanceRecord::factory()->create([
            'user_id' => $targetUser->id,
            'date' => '2026-08-03',
            'clock_in' => '09:10:00',
            'clock_out' => '18:10:00',
        ]);

        $initialResponse = $this->actingAs($admin)
            ->get("/admin/attendance/staff/{$targetUser->id}");

        $initialResponse->assertSee('?date=2026-07');

        // 「前月」の押下をリクエストで再現
        $response = $this->actingAs($admin)
            ->get("/admin/attendance/staff/{$targetUser->id}?date=2026-07");

        $response->assertStatus(200);
        $response->assertSeeText('2026/07');
        $response->assertSeeText('07/06(月)');
        $response->assertSeeText('08:45');
        $response->assertSeeText('17:45');
        $response->assertDontSeeText('09:10');
    }

    // 「翌月」を押下した時に表示月の翌月の情報が表示される
    public function test_next_month_displays_next_month_attendance(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $targetUser = User::factory()->create([
            'admin_status' => false,
        ]);

        // 対象ユーザーの8月勤怠
        AttendanceRecord::factory()->create([
            'user_id' => $targetUser->id,
            'date' => '2026-08-03',
            'clock_in' => '09:10:00',
            'clock_out' => '18:10:00',
        ]);

        // 対象ユーザーの9月勤怠
        AttendanceRecord::factory()->create([
            'user_id' => $targetUser->id,
            'date' => '2026-09-07',
            'clock_in' => '10:00:00',
            'clock_out' => '19:00:00',
        ]);

        $initialResponse = $this->actingAs($admin)
            ->get("/admin/attendance/staff/{$targetUser->id}");

        $initialResponse->assertSee('?date=2026-09');

        // 「翌月」の押下をリクエストで再現
        $response = $this->actingAs($admin)
            ->get("/admin/attendance/staff/{$targetUser->id}?date=2026-09");

        $response->assertStatus(200);
        $response->assertSeeText('2026/09');
        $response->assertSeeText('09/07(月)');
        $response->assertSeeText('10:00');
        $response->assertSeeText('19:00');
        $response->assertDontSeeText('09:10');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
