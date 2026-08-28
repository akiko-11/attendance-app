<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationListMultipleTest extends TestCase
{
    use RefreshDatabase;

    // 複数の申請がすべて表示される
    public function test_all_user_applications_are_displayed(): void
    {

        Carbon::setTestNow('2026-08-28 10:00:00');

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        // 勤怠レコードを複数準備
        $firstAttendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-08-24',
            'clock_in' => '09:01:00',
            'clock_out' => '18:01:00',
        ]);

        $secondAttendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-08-25',
            'clock_in' => '09:02:00',
            'clock_out' => '18:00:00',
        ]);

        $thirdAttendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-08-26',
            'clock_in' => '09:00:00',
            'clock_out' => '18:03:00',
        ]);

        $fourthAttendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-08-27',
            'clock_in' => '09:00:00',
            'clock_out' => '18:04:00',
        ]);

        // 承認待ち申請を複数準備
        AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $firstAttendanceRecord->id,
            'new_date' => '2026-08-24',
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'comment' => '承認待ち申請1',
            'approval_status' => false,
        ]);

        AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $secondAttendanceRecord->id,
            'new_date' => '2026-08-25',
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'comment' => '承認待ち申請2',
            'approval_status' => false,
        ]);

        // 承認済み申請を複数準備
        AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $thirdAttendanceRecord->id,
            'new_date' => '2026-08-26',
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'comment' => '承認済み申請1',
            'approval_status' => true,
        ]);

        AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $fourthAttendanceRecord->id,
            'new_date' => '2026-08-27',
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'comment' => '承認済み申請2',
            'approval_status' => true,
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);

        // 作成した申請がすべて表示されることを確認
        $response->assertSee('承認待ち申請1');
        $response->assertSee('承認待ち申請2');
        $response->assertSee('承認済み申請1');
        $response->assertSee('承認済み申請2');

        Carbon::setTestNow();
    }
}
