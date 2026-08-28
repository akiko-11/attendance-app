<?php

namespace Tests\Feature;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationListTabTest extends TestCase
{
    use RefreshDatabase;

    // 承認待ち・承認済み申請がそれぞれ正しいタブに表示される
    public function test_applications_are_displayed_in_correct_tabs(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        // 承認待ち申請の対象勤怠
        $pendingAttendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-08-26',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
        ]);

        // 承認済み申請の対象勤怠
        $approvedAttendanceRecord = AttendanceRecord::create([
            'user_id' => $user->id,
            'date' => '2026-08-27',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
        ]);

        // 承認待ち申請：「承認待ちテスト申請」
        AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $pendingAttendanceRecord->id,
            'new_date' => '2026-08-26',
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'comment' => '承認待ちテスト申請',
            'approval_status' => false,
        ]);

        // 承認済み申請：「承認済みテスト申請」
        AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $approvedAttendanceRecord->id,
            'new_date' => '2026-08-27',
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'comment' => '承認済みテスト申請',
            'approval_status' => true,
        ]);

        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);

        $response->assertSeeInOrder([
            'id="content1"',
            '承認待ちテスト申請',
            'id="content2"',
            '承認済みテスト申請',
        ], false);
    }
}
