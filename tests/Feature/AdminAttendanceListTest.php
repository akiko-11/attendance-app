<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    // 指定日の全ユーザーの勤怠情報が正確に表示される
    public function test_daily_attendance_records_are_displayed(): void
    {
        Carbon::setTestNow('2026-08-31 19:00:00');

        $admin = User::factory()->create([
            'name' => '管理者テスト',
            'admin_status' => true,
        ]);

        $user1 = User::factory()->create([
            'name' => 'ユーザー1',
            'admin_status' => false,
        ]);

        // 管理者の勤怠
        $adminAttendanceRecord = AttendanceRecord::create([
            'user_id' => $admin->id,
            'date' => '2026-08-31',
            'clock_in' => '09:30:00',
            'clock_out' => '19:00:00',
        ]);

        // ユーザー1の勤怠
        $user1AttendanceRecord = AttendanceRecord::create([
            'user_id' => $user1->id,
            'date' => '2026-08-31',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 管理者の休憩
        AttendanceBreak::create([
            'attendance_record_id' => $adminAttendanceRecord->id,
            'break_in' => '12:30:00',
            'break_out' => '13:30:00',
        ]);

        // ユーザー1の休憩
        AttendanceBreak::create([
            'attendance_record_id' => $user1AttendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-08-31');

        $response->assertStatus(200);

        // 日付
        $response->assertSee('2026/08/31');

        // 名前
        $response->assertSee('管理者テスト');
        $response->assertSee('ユーザー1');

        // 出勤・退勤
        $response->assertSee('09:30');
        $response->assertSee('19:00');
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        // 休憩
        $response->assertSee('1:00');

        // 合計
        $response->assertSee('8:30');
        $response->assertSee('8:00');

        Carbon::setTestNow();
    }

    // 未完了の勤怠項目は空欄で表示される
    public function test_incomplete_attendance_fields_are_blank(): void
    {
        Carbon::setTestNow('2026-08-31 10:00:00');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        // 出勤済み・未退勤の勤怠を準備
        $user1 = User::factory()->create([
            'name' => 'ユーザー1',
            'admin_status' => false,
        ]);

        $user1AttendanceRecord = AttendanceRecord::create([
            'user_id' => $user1->id,
            'date' => '2026-08-31',
            'clock_in' => '09:00:00',
            'clock_out' => null,
        ]);

        AttendanceBreak::create([
            'attendance_record_id' => $user1AttendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-08-31');

        $response->assertStatus(200);

        $blankField = '<p class="table__description--item"></p>';

        $response->assertSeeInOrder([
            'ユーザー1',
            '09:00',
            $blankField, // 退勤
            '1:00',
            $blankField, // 合計
        ], false);

        Carbon::setTestNow();
    }

    // 未完了の休憩は休憩時間に含めず空欄で表示される
    public function test_unfinished_break_is_not_displayed(): void
    {
        Carbon::setTestNow('2026-08-31 13:00:00');

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'ユーザー1',
            'admin_status' => false,
        ]);

        // 出勤済み・未退勤の勤怠を準備
        $userAttendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-08-31',
            'clock_in' => '09:00:00',
            'clock_out' => null,
        ]);

        // 開始済み・未終了の休憩を準備
        AttendanceBreak::create([
            'attendance_record_id' => $userAttendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => null,
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/attendance/list?date=2026-08-31');

        $response->assertStatus(200);

        $blankField = '<p class="table__description--item"></p>';

        $response->assertSeeInOrder([
            'ユーザー1',
            '09:00',
            $blankField, // 退勤
            $blankField, // 休憩
            $blankField, // 合計
        ], false);

        Carbon::setTestNow();
    }
}
