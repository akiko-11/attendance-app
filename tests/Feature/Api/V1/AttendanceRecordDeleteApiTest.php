<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordDeleteApiTest extends TestCase
{
    use RefreshDatabase;

    // 本人が自分の勤怠をDELETEできる
    public function test_authenticated_user_can_delete_attendance_record(): void
    {
        $user = $this->createAuthenticatedUser();

        $attendanceRecord = AttendanceRecord::factory()
            ->for($user)
            ->create([
                'date' => '2026-09-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '元の勤怠',
            ]);

        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('attendance_records', [
            'id' => $attendanceRecord->id,
        ]);
    }

    // 存在しないIDに対してDELETEすると404が返る
    public function test_returns_404_when_deleting_nonexistent_attendance_record_by_json(): void
    {
        $user = $this->createAuthenticatedUser();

        $attendanceRecord = AttendanceRecord::factory()
            ->for($user)
            ->create([
                'date' => '2026-09-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '元の勤怠',
            ]);

        $response = $this->deleteJson(
            '/api/v1/attendance-records/99999'
        );

        $response->assertStatus(404);

        $response->assertExactJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);

        // 既存の勤怠には影響していない
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
        ]);
    }

    private function createAuthenticatedUser(): User
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        return $user;
    }
}
