<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionRequestListTest extends TestCase
{
    use RefreshDatabase;

    // 修正申請が管理者と本人の申請一覧に表示される
    public function test_submitted_application_appears_in_both_lists(): void
    {
        // 管理者・一般ユーザー・一般ユーザーの勤怠を作成
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendanceRecord = $user->attendanceRecords()->create([
            'date' => '2026-09-05',
            'clock_in' => '08:05:00',
            'clock_out' => '17:05:00',
        ]);

        // 一般ユーザーとして、本人の勤怠の修正申請をPOST
        $response = $this->actingAs($user)
            ->post("/attendance/{$attendanceRecord->id}", [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'new_break_in' => ['12:00'],
                'new_break_out' => ['13:00'],
                'comment' => '打刻ミスのため',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(
            "/attendance/detail/{$attendanceRecord->id}"
        );

        // 保存された申請を取得
        $application = $attendanceRecord->attendanceCorrectionRequests()
            ->sole();

        // 本人の申請一覧に、対象申請が承認待ちで含まれることを確認
        $userResponse = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $userResponse->assertStatus(200);
        $userResponse->assertViewHas(
            'formattedApplications',
            function ($applications) use ($application) {
                $target = $applications->firstWhere('id', $application->id);

                return $target !== null
                    && $target['approval_status'] === '承認待ち';
            }
        );

        // 管理者の申請一覧にも、対象申請が承認待ちで含まれることを確認
        $adminResponse = $this->actingAs($admin)
            ->get('/stamp_correction_request/list');

        $adminResponse->assertStatus(200);
        $adminResponse->assertViewHas(
            'applications',
            function ($applications) use ($application) {
                $target = $applications->firstWhere('id', $application->id);

                return $target !== null
                    && $target->approval_status === '承認待ち';
            }
        );
    }
}
