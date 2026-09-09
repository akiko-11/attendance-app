<?php

namespace Tests\Feature\Application;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private AttendanceRecord $attendanceRecord;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'admin_status' => false,
        ]);

        $this->attendanceRecord = $this->user->attendanceRecords()->create([
            'date' => '2026-09-05',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
        ]);
    }

    // 出勤時刻が退勤時刻より後の場合、エラーになる
    public function test_clock_in_after_clock_out_is_rejected(): void
    {
        $response = $this->postCorrection([
            'new_clock_in' => '18:00',
            'new_clock_out' => '09:00',
        ]);

        $response->assertRedirect(
            "/attendance/detail/{$this->attendanceRecord->id}"
        );

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);

        $this->assertNoCorrectionRequestSaved();
    }

    // 退勤時刻が出勤時刻より前の場合、エラーになる
    public function test_clock_out_before_clock_in_is_rejected(): void
    {
        $response = $this->postCorrection([
            'new_clock_in' => '10:00',
            'new_clock_out' => '09:00',
        ]);

        $response->assertRedirect(
            "/attendance/detail/{$this->attendanceRecord->id}"
        );

        $response->assertSessionHasErrors([
            'new_clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);

        $this->assertNoCorrectionRequestSaved();
    }

    // 休憩開始時刻が出勤時刻より前の場合、エラーになる
    public function test_break_start_before_clock_in_is_rejected(): void
    {
        $response = $this->postCorrection([
            'new_break_in' => ['08:30'],
            'new_break_out' => ['10:00'],
        ]);

        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
        ]);

        $this->assertNoCorrectionRequestSaved();
    }

    // 休憩開始時刻が退勤時刻より後の場合、エラーになる
    public function test_break_start_after_clock_out_is_rejected(): void
    {
        $response = $this->postCorrection([
            'new_break_in' => ['18:30'],
            'new_break_out' => [''],
        ]);

        $response->assertSessionHasErrors([
            'new_break_in.0' => '休憩時間が不適切な値です',
        ]);

        $this->assertNoCorrectionRequestSaved();
    }

    // 休憩終了時刻が退勤時刻より後の場合、エラーになる
    public function test_break_end_after_clock_out_is_rejected(): void
    {
        $response = $this->postCorrection([
            'new_break_in' => ['17:30'],
            'new_break_out' => ['18:30'],
        ]);

        $response->assertSessionHasErrors([
            'new_break_out.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);

        $this->assertNoCorrectionRequestSaved();
    }

    // 備考が未入力の場合、エラーになる
    public function test_comment_is_required(): void
    {
        $response = $this->postCorrection([
            'comment' => '',
        ]);

        $response->assertSessionHasErrors([
            'comment' => '備考を記入してください',
        ]);

        $this->assertNoCorrectionRequestSaved();
    }

    private function postCorrection(array $overrides = [])
    {
        $data = array_merge([
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => [''],
            'new_break_out' => [''],
            'comment' => '打刻ミスのため',
        ], $overrides);

        return $this->actingAs($this->user)
            ->from("/attendance/detail/{$this->attendanceRecord->id}")
            ->post("/attendance/{$this->attendanceRecord->id}", $data);
    }

    private function assertNoCorrectionRequestSaved(): void
    {
        $this->assertSame(
            0,
            $this->attendanceRecord
                ->attendanceCorrectionRequests()
                ->count()
        );
    }
}
