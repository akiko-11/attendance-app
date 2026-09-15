<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecordApiTest extends TestCase
{
    use RefreshDatabase;

    // 勤怠一覧がJSONで取得できる
    public function test_can_get_attendance_record_list_by_json(): void
    {
        $user = User::factory()->create();

        AttendanceRecord::factory()
            ->count(3)
            ->for($user)
            ->sequence(
                ['date' => '2026-09-01'],
                ['date' => '2026-09-02'],
                ['date' => '2026-09-03'],
            )
            ->create();

        $response = $this->getJson('/api/v1/attendance-records');

        $response->assertStatus(200);

        $response->assertJsonCount(3, 'data');

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'user',
                    'date',
                    'clock_in',
                    'clock_out',
                    'total_time',
                    'total_break_time',
                    'comment',
                    'breaks',
                ],
            ],
            'links',
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);

        $response->assertJsonPath('meta.current_page', 1);
        $response->assertJsonPath('meta.per_page', 20);
        $response->assertJsonPath('meta.total', 3);
    }

    // 指定した勤怠詳細をJSONで取得できる
    public function test_can_get_attendance_record_detail_by_json(): void
    {
        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        $attendanceRecord = AttendanceRecord::factory()
            ->for($user)
            ->create([
                'date' => '2026-09-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '詳細確認テスト',
            ]);

        AttendanceBreak::factory()->create([
            'attendance_record_id' => $attendanceRecord->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        AttendanceCorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_record_id' => $attendanceRecord->id,
            'new_date' => '2026-09-02',
            'new_clock_in' => '09:30:00',
            'new_clock_out' => '18:30:00',
            'comment' => '修正申請テスト',
            'approval_status' => false,
        ]);

        $response = $this->getJson(
            "/api/v1/attendance-records/{$attendanceRecord->id}"
        );

        $response->assertStatus(200);

        $response->assertJsonPath(
            'data.id',
            $attendanceRecord->id
        );

        $response->assertJsonPath(
            'data.user_id',
            $user->id
        );

        $response->assertJsonPath(
            'data.user.name',
            'テストユーザー'
        );

        $response->assertJsonPath(
            'data.date',
            '2026-09-01'
        );

        $response->assertJsonPath(
            'data.comment',
            '詳細確認テスト'
        );

        $response->assertJsonCount(1, 'data.breaks');
        $response->assertJsonCount(1, 'data.applications');

        $response->assertJsonStructure([
            'data' => [
                'id',
                'user_id',
                'user' => [
                    'id',
                    'name',
                ],
                'date',
                'clock_in',
                'clock_out',
                'total_time',
                'total_break_time',
                'comment',
                'breaks' => [
                    '*' => [
                        'id',
                        'break_in',
                        'break_out',
                    ],
                ],
                'applications',
            ],
        ]);

        $response->assertJsonPath(
            'data.applications.0.attendance_record_id',
            $attendanceRecord->id
        );

        $response->assertJsonPath(
            'data.applications.0.comment',
            '修正申請テスト'
        );
    }

    // 存在しないIDでは404とエラーJSONが返る
    public function test_returns_404_when_attendance_record_does_not_exist_by_json(): void
    {
        $response = $this->getJson(
            '/api/v1/attendance-records/99999'
        );

        $response->assertStatus(404);

        $response->assertExactJson([
            'error' => '勤怠情報が見つかりませんでした。',
        ]);
    }
}
