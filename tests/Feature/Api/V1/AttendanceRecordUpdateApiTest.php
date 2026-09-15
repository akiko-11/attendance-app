<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    // 本人が自分の勤怠をUPDATEできる
    public function test_authenticated_user_can_update_attendance_record(): void
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

        $response->assertJsonPath(
            'data.date',
            '2026-09-01'
        );

        $response->assertJsonPath(
            'data.clock_in',
            '09:10:00'
        );

        $response->assertJsonPath(
            'data.clock_out',
            '18:10:00'
        );

        $response->assertJsonPath(
            'data.comment',
            '勤怠を更新しました。'
        );

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'clock_in' => '09:10:00',
            'clock_out' => '18:10:00',
            'comment' => '勤怠を更新しました。',
        ]);

        $attendanceRecord->refresh();

        $this->assertSame(
            '2026-09-01',
            $attendanceRecord->date->format('Y-m-d')
        );
    }

    // 本人が自分の勤怠をPATCHできる
    public function test_authenticated_user_can_patch_attendance_record(): void
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

        $response = $this->patchJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}",
            [
                'comment' => '勤怠を一部更新しました。',
            ]
        );

        $response->assertStatus(200);

        $response->assertJsonPath(
            'data.comment',
            '勤怠を一部更新しました。'
        );

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '勤怠を一部更新しました。',
        ]);
    }

    // 存在しないIDに対してUPDATEすると404が返る
    public function test_returns_404_when_updating_nonexistent_attendance_record_by_json(): void
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

        $response = $this->putJson(
            '/api/v1/attendance-records/999999',
            [
                'date' => '2026-09-01',
                'clock_in' => '09:10:00',
                'clock_out' => '18:10:00',
                'comment' => '勤怠を更新しました。',
            ]
        );

        $response->assertStatus(404);

        $response->assertExactJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);

        // 既存の勤怠には影響していない
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '元の勤怠',
        ]);
    }

    private function createAuthenticatedUser(): User
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        return $user;
    }
}
