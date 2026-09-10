<?php

namespace Tests\Feature\Admin;

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

        [$admin, $adminAttendance] = $this->createUserWithAttendance(
            [
                'name' => '管理者テスト',
                'admin_status' => true,
            ],
            [
                'clock_in' => '09:30:00',
                'clock_out' => '19:00:00',
            ],
            [
                'break_in' => '12:30:00',
                'break_out' => '13:30:00',
            ]
        );

        [$user, $userAttendance] = $this->createUserWithAttendance(
            [
                'name' => 'ユーザー1',
            ],
            [],
            []
        );

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
    }

    // 未完了の勤怠項目は空欄で表示される
    public function test_incomplete_attendance_fields_are_blank(): void
    {
        Carbon::setTestNow('2026-08-31 10:00:00');

        $admin = $this->createUser([
            'admin_status' => true,
        ]);

        [, $attendance] = $this->createUserWithAttendance(
            [
                'name' => 'ユーザー1',
            ],
            [
                'clock_out' => null,
            ],
            []
        );

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
    }

    // 未完了の休憩は休憩時間に含めず空欄で表示される
    public function test_unfinished_break_is_not_displayed(): void
    {
        Carbon::setTestNow('2026-08-31 13:00:00');

        $admin = $this->createUser([
            'admin_status' => true,
        ]);

        [, $attendance] = $this->createUserWithAttendance(
            [
                'name' => 'ユーザー1',
            ],
            [
                'clock_out' => null,
            ],
            [
                'break_out' => null,
            ]
        );

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
    }

    // テスト用ユーザーを作成する
    private function createUser(array $overrides = []): User
    {
        return User::factory()->create(
            array_merge([
                'admin_status' => false,
            ], $overrides)
        );
    }

    // テスト用ユーザーと勤怠、休憩を作成する
    private function createUserWithAttendance(
        array $userOverrides = [],
        array $attendanceOverrides = [],
        ?array $breakOverrides = null
    ): array {
        $user = $this->createUser($userOverrides);

        $attendance = AttendanceRecord::create(
            array_merge([
                'user_id' => $user->id,
                'date' => '2026-08-31',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
            ], $attendanceOverrides)
        );

        if ($breakOverrides !== null) {
            AttendanceBreak::create(
                array_merge([
                    'attendance_record_id' => $attendance->id,
                    'break_in' => '12:00:00',
                    'break_out' => '13:00:00',
                ], $breakOverrides)
            );
        }

        return [$user, $attendance];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }
}
