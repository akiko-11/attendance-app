<?php

namespace Tests\Feature\Application;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionRequestBoundaryValidationTest extends TestCase
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

    // 休憩終了時刻のみ入力した場合、エラーになる
    public function test_break_end_without_break_start_is_rejected(): void
    {
        $response = $this->postCorrection([
            'new_break_in' => [''],
            'new_break_out' => ['13:00'],
        ]);

        $response->assertSessionHasErrors('new_break_in.0');

        $this->assertNoCorrectionRequestSaved();
    }

    // 休憩終了時刻が休憩開始時刻より前の場合、エラーになる
    public function test_break_end_before_break_start_is_rejected(): void
    {
        $response = $this->postCorrection([
            'new_break_in' => ['13:00'],
            'new_break_out' => ['12:00'],
        ]);

        $response->assertSessionHasErrors('new_break_out.0');

        $this->assertNoCorrectionRequestSaved();
    }

    // 出勤時刻がH:i形式でない場合、エラーになる
    public function test_clock_in_with_invalid_time_format_is_rejected(): void
    {
        $response = $this->postCorrection([
            'new_clock_in' => 'invalid',
        ]);

        $response->assertSessionHasErrors('new_clock_in');

        $this->assertNoCorrectionRequestSaved();
    }

    // 休憩開始・終了に配列以外を送信した場合、エラーになる
    public function test_break_fields_must_be_arrays(): void
    {
        $response = $this->postCorrection([
            'new_break_in' => '12:00',
            'new_break_out' => '13:00',
        ]);

        $response->assertSessionHasErrors([
            'new_break_in',
            'new_break_out',
        ]);

        $this->assertNoCorrectionRequestSaved();
    }

    // 複数の休憩と空欄の追加行を送信しても、有効な休憩だけ保存される
    public function test_multiple_breaks_with_empty_extra_row_are_saved_correctly(): void
    {
        $response = $this->postCorrection([
            'new_break_in' => [
                '12:00',
                '15:00',
                '',
            ],
            'new_break_out' => [
                '13:00',
                '15:30',
                '',
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $application = $this->attendanceRecord
            ->attendanceCorrectionRequests()
            ->firstOrFail();

        $this->assertSame(
            2,
            $application->proposalBreaks()->count()
        );

        $this->assertDatabaseHas('proposal_breaks', [
            'attendance_correction_request_id' => $application->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        $this->assertDatabaseHas('proposal_breaks', [
            'attendance_correction_request_id' => $application->id,
            'break_in' => '15:00',
            'break_out' => '15:30',
        ]);
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
