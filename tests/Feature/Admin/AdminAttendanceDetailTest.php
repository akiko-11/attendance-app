<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    // 管理者が選択した勤怠情報が詳細画面に表示される
    public function test_admin_can_view_selected_attendance_detail(): void
    {
        [$admin, , $attendance] = $this->createAttendanceData();

        $response = $this
            ->actingAs($admin)
            ->get("/admin/attendance/{$attendance->id}");

        $response->assertStatus(200);

        $response->assertSee('テストユーザー');
        $response->assertSee('2026年');
        $response->assertSee('9月1日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('通常勤務');
    }

    // 正常な修正内容がDBへ反映される
    public function test_admin_can_update_attendance(): void
    {
        [$admin, $user, $attendance] = $this->createAttendanceData();

        $response = $this->postAttendanceUpdate($admin, $attendance, [
            'new_clock_in' => '09:10',
            'new_clock_out' => '18:10',
            'new_break_in' => [0 => '12:10'],
            'new_break_out' => [0 => '13:10'],
            'comment' => '管理者修正テスト',
        ]);

        // attendance_records が更新されたことを確認
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendance->id,
            'user_id' => $user->id,
            'clock_in' => '09:10',
            'clock_out' => '18:10',
            'comment' => '管理者修正テスト',
        ]);

        // attendance_breaks が更新されたことを確認
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:10',
            'break_out' => '13:10',
        ]);

        // 元の休憩がattendance_breaksに残っていないことを確認
        $this->assertDatabaseMissing('attendance_breaks', [
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        // リダイレクトの確認
        $response->assertRedirect("/admin/attendance/{$attendance->id}");

        // 修正後の内容が一般ユーザーの勤怠詳細にも表示される
        $userResponse = $this->actingAs($user)
            ->get("/attendance/detail/{$attendance->id}");

        $userResponse->assertStatus(200);
        $userResponse->assertSee('value="09:10"', false);
        $userResponse->assertSee('value="18:10"', false);
        $userResponse->assertSee('value="12:10"', false);
        $userResponse->assertSee('value="13:10"', false);
        $userResponse->assertSee('管理者修正テスト');
    }

    // テストの前提条件（ユーザー、勤怠、休憩の作成）
    private function createAttendanceData(): array
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'admin_status' => false,
        ]);

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-01',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '通常勤務',
        ]);

        AttendanceBreak::factory()->create([
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        return [$admin, $user, $attendance];
    }

    // 正常な入力値
    private function validUpdateData(array $overrides = []): array
    {
        return array_merge([
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => [0 => '12:00'],
            'new_break_out' => [0 => '13:00'],
            'comment' => '通常勤務',
        ], $overrides);
    }

    // 管理者として勤怠の修正内容を送信する
    private function postAttendanceUpdate(
        User $admin,
        AttendanceRecord $attendance,
        array $overrides
    ): TestResponse {
        return $this->actingAs($admin)->post(
            "/attendance/{$attendance->id}",
            $this->validUpdateData($overrides)
        );
    }
}
