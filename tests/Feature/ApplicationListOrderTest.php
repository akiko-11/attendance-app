<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationListOrderTest extends TestCase
{
    use RefreshDatabase;

    // 本人と管理者の申請一覧が申請日時の新しい順に並ぶ
    public function test_application_lists_are_ordered_by_latest_submission(): void
    {
        // 一般ユーザーと勤怠を作成
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendanceRecord = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-04',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '修正前の備考',
        ]);

        // 申請日時が異なる修正申請を作成
        // 新しい申請を先に作成
        $newerApplication = $attendanceRecord->attendanceCorrectionRequests()->create([
            'user_id' => $user->id,
            'new_date' => $attendanceRecord->date,
            'new_clock_in' => '09:10:00',
            'new_clock_out' => '18:10:00',
            'comment' => '新しい申請',
            'approval_status' => true,
        ]);

        $newerApplication->created_at = '2026-09-06 10:00:00';
        $newerApplication->save();

        // 古い申請を後に作成
        $olderApplication = $attendanceRecord->attendanceCorrectionRequests()->create([
            'user_id' => $user->id,
            'new_date' => $attendanceRecord->date,
            'new_clock_in' => '09:20:00',
            'new_clock_out' => '18:20:00',
            'comment' => '古い申請',
            'approval_status' => true,
        ]);

        $olderApplication->created_at = '2026-09-05 10:00:00';
        $olderApplication->save();

        // 本人として申請一覧へGET
        $response = $this->actingAs($user)
            ->get('/stamp_correction_request/list');

        $response->assertStatus(200);

        // 新しい申請 → 古い申請の順になっていることを確認
        $this->assertSame(
            [$newerApplication->id, $olderApplication->id],
            $response->viewData('formattedApplications')->pluck('id')->all()
        );

        // 管理者の申請一覧も同じ順序であることを確認
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $adminResponse = $this->actingAs($admin)
            ->get('/stamp_correction_request/list');

        $adminResponse->assertStatus(200);

        $this->assertSame(
            [$newerApplication->id, $olderApplication->id],
            $adminResponse->viewData('applications')->pluck('id')->all()
        );
    }
}
