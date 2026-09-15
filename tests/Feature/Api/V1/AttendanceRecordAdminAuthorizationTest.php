<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordAdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // 管理者が一般ユーザーの勤怠をPUTで更新できる
    public function test_admin_can_update_other_users_attendance_record_by_json(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $attendanceRecord = AttendanceRecord::factory()
            ->for($user)
            ->create([
                'date' => '2026-09-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '元の勤怠',
            ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}",
            [
                'date' => '2026-09-01',
                'clock_in' => '09:10:00',
                'clock_out' => '18:10:00',
                'comment' => '勤怠を更新しました。',
            ]
        );

        $response->assertStatus(200);

        $response->assertJsonPath(
            'data.user_id',
            $user->id
        );

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'clock_in' => '09:10:00',
            'clock_out' => '18:10:00',
            'comment' => '勤怠を更新しました。',
        ]);
    }

    // 管理者が一般ユーザーの勤怠をDELETEで削除できる
    public function test_admin_can_delete_other_users_attendance_record_by_json(): void
    {
        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $attendanceRecord = AttendanceRecord::factory()
            ->for($user)
            ->create([
                'date' => '2026-09-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '元の勤怠',
            ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}"
        );

        $response->assertStatus(204);

        $this->assertDatabaseMissing('attendance_records', [
            'id' => $attendanceRecord->id,
        ]);
    }
}
