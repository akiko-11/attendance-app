<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordCreateFieldValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();

        Sanctum::actingAs($user);
    }

    // clock_in未入力時に422と日本語エラーメッセージが返る
    public function test_returns_422_with_japanese_error_message_when_clock_in_is_missing_by_json(): void
    {
        $response = $this->postJson(
            '/api/v1/attendance-records',
            [
                'date' => '2026-09-01',
                'clock_out' => '18:00:00',
                'comment' => '勤怠作成',
            ]
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'clock_in',
        ]);

        $response->assertJsonPath(
            'errors.clock_in.0',
            '出勤時刻は必須です。'
        );
    }

    // clock_in形式不正時に422と日本語エラーメッセージが返る
    public function test_returns_422_with_japanese_error_message_when_clock_in_format_is_invalid_by_json(): void
    {
        $response = $this->postJson(
            '/api/v1/attendance-records',
            [
                'date' => '2026-09-01',
                'clock_in' => '09-00-00',
                'clock_out' => '18:00:00',
                'comment' => '勤怠作成',
            ]
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'clock_in',
        ]);

        $response->assertJsonPath(
            'errors.clock_in.0',
            '出勤時刻は HH:MM:SS 形式で指定してください。'
        );
    }

    // clock_out形式不正時に422と日本語エラーメッセージが返る
    public function test_returns_422_with_japanese_error_message_when_clock_out_format_is_invalid_by_json(): void
    {
        $response = $this->postJson(
            '/api/v1/attendance-records',
            [
                'date' => '2026-09-01',
                'clock_in' => '09:00:00',
                'clock_out' => '180:00:00',
                'comment' => '勤怠作成',
            ]
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'clock_out',
        ]);

        $response->assertJsonPath(
            'errors.clock_out.0',
            '退勤時刻は HH:MM:SS 形式で指定してください。'
        );
    }

    // clock_outがclock_inより前の場合に422と日本語エラーメッセージが返る
    public function test_returns_422_with_japanese_error_message_when_clock_out_is_not_after_clock_in_by_json(): void
    {
        $response = $this->postJson(
            '/api/v1/attendance-records',
            [
                'date' => '2026-09-01',
                'clock_in' => '09:00:00',
                'clock_out' => '08:00:00',
                'comment' => '勤怠作成',
            ]
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'clock_out',
        ]);

        $response->assertJsonPath(
            'errors.clock_out.0',
            '退勤時刻は出勤時刻より後の時刻を指定してください。'
        );
    }

    // commentが255文字を超える場合に422と日本語エラーメッセージが返る
    public function test_returns_422_with_japanese_error_message_when_comment_exceeds_255_characters_by_json(): void
    {
        $response = $this->postJson(
            '/api/v1/attendance-records',
            [
                'date' => '2026-09-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => str_repeat('あ', 256),
            ]
        );

        $response->assertStatus(422);

        $response->assertJsonValidationErrors([
            'comment',
        ]);

        $response->assertJsonPath(
            'errors.comment.0',
            '備考は 255 文字以内で入力してください。'
        );
    }
}
