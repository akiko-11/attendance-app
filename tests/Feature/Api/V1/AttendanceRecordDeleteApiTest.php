<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    // 勤怠を削除すると関連する休憩と修正申請も削除される
    public function test_deleting_attendance_record_cascades_related_records(): void
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

        $breakId = DB::table('attendance_breaks')->insertGetId([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $correctionRequestId = DB::table('attendance_correction_requests')
            ->insertGetId([
                'user_id' => $user->id,
                'attendance_record_id' => $attendanceRecord->id,
                'new_date' => '2026-09-01',
                'new_clock_in' => '09:30:00',
                'new_clock_out' => '18:00:00',
                'comment' => '修正申請',
                'approval_status' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $response = $this->deleteJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}"
        );

        $response->assertNoContent();

        $this->assertDatabaseMissing('attendance_records', [
            'id' => $attendanceRecord->id,
        ]);

        $this->assertDatabaseMissing('attendance_breaks', [
            'id' => $breakId,
        ]);

        $this->assertDatabaseMissing('attendance_correction_requests', [
            'id' => $correctionRequestId,
        ]);
    }

    private function createAuthenticatedUser(): User
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        return $user;
    }
}
