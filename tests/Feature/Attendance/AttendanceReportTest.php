<?php

namespace Tests\Feature\Attendance;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    // ゲストはマイ勤怠レポートにアクセスできない
    public function test_guest_cannot_access_attendance_report(): void
    {
        $response = $this->get('/attendance/report');

        $response->assertRedirect('/login');
    }

    // 認証ユーザーの勤怠統計が正しく計算される
    public function test_authenticated_user_can_view_correct_attendance_report(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
            'email_verified_at' => now(),
        ]);

        // ここで3日分のAttendanceRecordと休憩を作成
        $firstAttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-01',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        $secondAttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-02',
            'clock_in' => '09:00:00',
            'clock_out' => '19:00:00',
        ]);

        $thirdAttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-03',
            'clock_in' => '09:00:00',
            'clock_out' => '17:00:00',
        ]);

        AttendanceBreak::factory()->create([
            'attendance_record_id' => $firstAttendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        AttendanceBreak::factory()->create([
            'attendance_record_id' => $secondAttendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        AttendanceBreak::factory()->create([
            'attendance_record_id' => $thirdAttendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/report');

        $response->assertStatus(200);

        // 基本サマリーの確認
        $response->assertViewHas('summary', [
            'total_work_minutes' => 1440,
            'total_overtime_minutes' => 60,
            'avg_work_minutes' => 480,
        ]);

        // 月次推移の確認
        $response->assertViewHas('monthlyTrend', function ($monthlyTrend) {
            $september = collect($monthlyTrend)
                ->firstWhere('month', '2026-09');

            return $september['work_minutes'] === 1440
                && $september['overtime_minutes'] === 60;
        });

        // 今月の異常検知の確認
        $response->assertViewHas('anomalies', [
            'late_count' => 0,
            'early_leave_count' => 1,
            'long_work_count' => 0,
        ]);
    }

    // 勤怠記録がないユーザーでもレポートを表示できる
    public function test_user_without_attendance_records_can_view_report_safely(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/report');

        $response->assertStatus(200);

        $response->assertViewHas('summary', [
            'total_work_minutes' => 0,
            'total_overtime_minutes' => 0,
            'avg_work_minutes' => 0,
        ]);

        $response->assertViewHas('anomalies', [
            'late_count' => 0,
            'early_leave_count' => 0,
            'long_work_count' => 0,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            Carbon::create(2026, 9, 13, 12, 0, 0)
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
