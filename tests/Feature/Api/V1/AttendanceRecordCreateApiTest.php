<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordCreateApiTest extends TestCase
{
    use RefreshDatabase;

    // 勤怠が作成される
    public function test_authenticated_user_can_create_attendance_record(): void
    {
        $user = $this->createAuthenticatedUser();

        $response = $this->postJson(
            '/api/v1/attendance-records',
            [
                'date' => '2026-09-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '勤怠作成',
            ]
        );

        $response->assertStatus(201);

        // レスポンスJSONの確認
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
            '09:00:00'
        );

        $response->assertJsonPath(
            'data.clock_out',
            '18:00:00'
        );

        $response->assertJsonPath(
            'data.comment',
            '勤怠作成'
        );

        // attendance_records テーブルに保存されたことを確認
        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $user->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '勤怠作成',
        ]);

        $attendanceRecord = AttendanceRecord::where(
            'user_id',
            $user->id
        )->first();

        // dateはSQLiteで 00:00:00 が付くため、モデル経由で確認
        $this->assertSame(
            '2026-09-01',
            $attendanceRecord->date->format('Y-m-d')
        );
    }

    private function createAuthenticatedUser(): User
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        return $user;
    }
}
