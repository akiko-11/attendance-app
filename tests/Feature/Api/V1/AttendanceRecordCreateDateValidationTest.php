<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordCreateDateValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Sanctum::actingAs($this->user);
    }

    // date未送信時に422と日本語エラーメッセージが返る
    public function test_returns_422_with_japanese_error_message_when_date_is_missing_by_json(): void
    {
        $response = $this->postJson(
            '/api/v1/attendance-records',
            [
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '勤怠作成',
            ]
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'date',
        ]);

        $response->assertJsonPath(
            'errors.date.0',
            '勤怠日は必須です。'
        );
    }

    // date形式不正時に422と日本語エラーメッセージが返る
    public function test_returns_422_with_japanese_error_message_when_date_format_is_invalid_by_json(): void
    {
        $response = $this->postJson(
            '/api/v1/attendance-records',
            [
                'date' => '2026/09/01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '勤怠作成',
            ]
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'date',
        ]);

        $response->assertJsonPath(
            'errors.date.0',
            '勤怠日は YYYY-MM-DD 形式で指定してください。'
        );
    }

    // user_id × date 重複時に422と日本語エラーメッセージが返る
    public function test_returns_422_with_japanese_error_message_when_date_is_duplicated_by_json(): void
    {
        AttendanceRecord::factory()
            ->for($this->user)
            ->create([
                'date' => '2026-09-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '既に存在する勤怠',
            ]);

        $response = $this->postJson(
            '/api/v1/attendance-records',
            [
                'date' => '2026-09-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '勤怠作成',
            ]
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'date',
        ]);

        $response->assertJsonPath(
            'errors.date.0',
            'この日付の勤怠は既に登録されています。'
        );
    }
}
