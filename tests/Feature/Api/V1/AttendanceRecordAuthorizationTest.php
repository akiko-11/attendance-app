<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // 未認証ユーザーは勤怠を作成することはできない
    public function test_unauthenticated_user_cannot_create_attendance_record_by_json(): void
    {
        $response = $this->postJson(
            '/api/v1/attendance-records',
            [
                'date' => '2026-09-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '勤怠作成',
            ]
        );

        $response->assertStatus(401);

        $response->assertExactJson([
            'message' => 'Unauthenticated.',
        ]);
    }

    // 未認証ユーザーは勤怠を更新できない
    public function test_unauthenticated_user_cannot_update_attendance_record_by_json(): void
    {
        $owner = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()
            ->for($owner)
            ->create([
                'date' => '2026-09-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '元の勤怠',
            ]);

        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}",
            [
                'date' => '2026-09-01',
                'clock_in' => '09:10:00',
                'clock_out' => '18:10:00',
                'comment' => '勤怠を更新しました。',
            ]
        );

        $response->assertStatus(401);

        $response->assertExactJson([
            'message' => 'Unauthenticated.',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '元の勤怠',
        ]);
    }

    // 未認証ユーザーは勤怠を削除できない
    public function test_unauthenticated_user_cannot_delete_attendance_record_by_json(): void
    {
        $owner = User::factory()->create();

        $attendanceRecord = AttendanceRecord::factory()
            ->for($owner)
            ->create([
                'date' => '2026-09-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '元の勤怠',
            ]);

        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}"
        );

        $response->assertStatus(401);

        $response->assertExactJson([
            'message' => 'Unauthenticated.',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
        ]);
    }

    // 他のユーザーは本人以外の勤怠を更新できない
    public function test_authenticated_user_cannot_update_other_users_attendance_record_by_json(): void
    {
        $owner = User::factory()->create([
            'admin_status' => false,
        ]);

        $otherUser = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendanceRecord = AttendanceRecord::factory()
            ->for($owner)
            ->create([
                'date' => '2026-09-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '元の勤怠',
            ]);

        Sanctum::actingAs($otherUser);

        $response = $this->putJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}",
            [
                'date' => '2026-09-01',
                'clock_in' => '09:10:00',
                'clock_out' => '18:10:00',
                'comment' => '勤怠を更新しました。',
            ]
        );

        $response->assertStatus(403);

        $response->assertExactJson([
            'error' => 'この操作を実行する権限がありません。',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '元の勤怠',
        ]);
    }

    // 他のユーザーは本人以外の勤怠を削除できない
    public function test_authenticated_user_cannot_delete_other_users_attendance_record_by_json(): void
    {
        $owner = User::factory()->create([
            'admin_status' => false,
        ]);

        $otherUser = User::factory()->create([
            'admin_status' => false,
        ]);

        $attendanceRecord = AttendanceRecord::factory()
            ->for($owner)
            ->create([
                'date' => '2026-09-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '元の勤怠',
            ]);

        Sanctum::actingAs($otherUser);

        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}"
        );

        $response->assertStatus(403);

        $response->assertExactJson([
            'error' => 'この操作を実行する権限がありません。',
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '元の勤怠',
        ]);
    }
}
