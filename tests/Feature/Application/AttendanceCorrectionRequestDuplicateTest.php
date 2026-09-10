<?php

namespace Tests\Feature\Application;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionRequestDuplicateTest extends TestCase
{
    use RefreshDatabase;

    // 承認待ちの申請がある場合、新たな修正申請は保存されない
    public function test_user_cannot_submit_another_pending_application(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        // 一般ユーザーの勤怠を作成
        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-09-05',
            'clock_in' => '08:00:00',
            'clock_out' => '17:00:00',
            'comment' => '打刻ミスのため',
        ]);

        // その勤怠に承認待ちの申請を作成
        $application = $attendanceRecord->attendanceCorrectionRequests()->create([
            'user_id' => $user->id,
            'new_date' => $attendanceRecord->date,
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'comment' => '打刻ミスのため',
            'approval_status' => false,
        ]);

        // 既存の申請とは異なる内容でPOST
        $response = $this->actingAs($user)
            ->post("/attendance/{$attendanceRecord->id}", [
                'new_clock_in' => '09:10',
                'new_clock_out' => '18:10',
                'new_break_in' => ['12:30', ''],
                'new_break_out' => ['13:30', ''],
                'comment' => '既存の申請とは異なる',
            ]);

        // バリデーションを通過し、同じ勤怠詳細へ戻ることを確認
        $response->assertSessionHasNoErrors();
        $response->assertRedirect("/attendance/detail/{$attendanceRecord->id}");

        // 申請が追加されず、1件のままであることを確認
        $this->assertSame(
            1,
            $attendanceRecord->attendanceCorrectionRequests()->count()
        );

        // 既存の申請内容が変更されていないことを確認
        $this->assertDatabaseHas('attendance_correction_requests', [
            'id' => $application->id,
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'comment' => '打刻ミスのため',
            'approval_status' => false,
        ]);

        // POSTした休憩も既存の申請へ追加されていないことを確認
        $this->assertSame(0, $application->proposalBreaks()->count());
    }
}
