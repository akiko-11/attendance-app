<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AdminAttendanceValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private AttendanceRecord $attendance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $this->attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-01',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '通常勤務',
        ]);

        AttendanceBreak::factory()->create([
            'attendance_record_id' => $this->attendance->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);
    }

    // 出勤時間が退勤時間より後の場合エラーになる
    public function test_clock_in_after_clock_out_is_invalid(): void
    {
        $response = $this->postAttendanceUpdate([
            'new_clock_in' => '19:00',
        ]);

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    // 退勤時間が出勤時間より前の場合エラーになる
    public function test_clock_out_before_clock_in_is_invalid(): void
    {
        $response = $this->postAttendanceUpdate([
            'new_clock_out' => '08:00',
        ]);

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    // 休憩開始時間が出勤時間より前の場合エラーになる
    public function test_break_in_before_clock_in_is_invalid(): void
    {
        $response = $this->postAttendanceUpdate([
            'new_break_in' => [0 => '08:00'],
            'new_break_out' => [0 => '10:00'],
        ]);

        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
        ]);
    }

    // 休憩開始時間が退勤時間より後の場合エラーになる
    public function test_break_in_after_clock_out_is_invalid(): void
    {
        $response = $this->postAttendanceUpdate([
            'new_break_in' => [0 => '19:00'],
            'new_break_out' => [0 => ''],
        ]);

        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
        ]);
    }

    // 休憩終了時間が退勤時間より後の場合エラーになる
    public function test_break_out_after_clock_out_is_invalid(): void
    {
        $response = $this->postAttendanceUpdate([
            'new_break_out' => [0 => '19:00'],
        ]);

        $response->assertSessionHasErrors([
            'new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    // 備考が未入力の場合エラーになる
    public function test_comment_is_required(): void
    {
        $response = $this->postAttendanceUpdate([
            'comment' => '',
        ]);

        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);
    }

    private function postAttendanceUpdate(array $overrides = []): TestResponse
    {
        $data = array_merge([
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => [0 => '12:00'],
            'new_break_out' => [0 => '13:00'],
            'comment' => '通常勤務',
        ], $overrides);

        return $this->actingAs($this->admin)
            ->post("/attendance/{$this->attendance->id}", $data);
    }
}
