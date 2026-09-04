<?php

namespace Tests\Feature;

use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApplicationApprovalListTest extends TestCase
{
    use RefreshDatabase;

    // 承認結果が管理者・一般ユーザーの申請一覧に反映される
    public function test_approval_status_is_reflected_in_both_application_lists(): void
    {
        // 管理者・一般ユーザー・勤怠・未承認の修正申請を作成
        $admin = User::factory()->create([
            'name' => '管理者',
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'name' => 'ユーザー',
            'admin_status' => false,
        ]);

        $userAttendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '修正前の備考',
        ]);

        AttendanceBreak::factory()->create([
            'attendance_record_id' => $userAttendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        // 承認待ちの修正申請を作成
        $application = AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $userAttendanceRecord->id,
            'new_date' => '2026-09-05',
            'new_clock_in' => '09:10:00',
            'new_clock_out' => '18:10:00',
            'comment' => '打刻ミスのため',
            'approval_status' => false,
        ]);

        $application->proposalBreaks()->create([
            'break_in' => '12:15:00',
            'break_out' => '13:15:00',
        ]);

        // 管理者の申請一覧で「承認待ち」であることを確認
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

        // 一般ユーザーの申請一覧でも「承認待ち」であることを確認
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

        // 管理者として対象の申請を承認
        $url = "/stamp_correction_request/approve/{$application->id}";

        $this->actingAs($admin)
            ->post($url)
            ->assertRedirect($url);

        // 承認後、管理者の申請一覧を再取得
        $adminResponse = $this->actingAs($admin)
            ->get('/stamp_correction_request/list');

        $adminResponse->assertStatus(200);
        $adminResponse->assertViewHas(
            'applications',
            function ($applications) use ($application) {
                $target = $applications->firstWhere('id', $application->id);

                return $target !== null
                    && $target->approval_status === '承認済み';
            }
        );

        // 承認後、一般ユーザーの申請一覧を再取得
        $userResponse = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $userResponse->assertStatus(200);
        $userResponse->assertViewHas(
            'formattedApplications',
            function ($applications) use ($application) {
                $target = $applications->firstWhere('id', $application->id);

                return $target !== null
                    && $target['approval_status'] === '承認済み';
            }
        );
    }
}
