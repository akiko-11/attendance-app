<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecordBoundaryTest extends TestCase
{
    use RefreshDatabase;

    // per_page の最大値100を指定できる
    public function test_per_page_accepts_maximum_value_of_100(): void
    {
        $user = User::factory()->create();

        AttendanceRecord::factory()
            ->for($user)
            ->create([
                'date' => '2026-09-01',
            ]);

        $response = $this->getJson(
            '/api/v1/attendance-records?per_page=100'
        );

        $response->assertStatus(200);
        $response->assertJsonPath('meta.per_page', 100);
    }

    // per_page が最大値100を超えると422が返る
    public function test_returns_422_when_per_page_exceeds_100(): void
    {
        $response = $this->getJson(
            '/api/v1/attendance-records?per_page=101'
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'per_page',
        ]);
        $response->assertJsonPath(
            'errors.per_page.0',
            '1ページあたりの件数は100以下で指定してください。'
        );
    }

    // date の形式が不正な場合422が返る
    public function test_returns_422_when_date_format_is_invalid(): void
    {
        $response = $this->getJson(
            '/api/v1/attendance-records?date=2026-09'
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'date',
        ]);
        $response->assertJsonPath(
            'errors.date.0',
            '日付は YYYY-MM-DD 形式で指定してください。'
        );
    }

    // month の形式が不正な場合422が返る
    public function test_returns_422_when_month_format_is_invalid(): void
    {
        $response = $this->getJson(
            '/api/v1/attendance-records?month=2026-09-01'
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'month',
        ]);
        $response->assertJsonPath(
            'errors.month.0',
            '年月は YYYY-MM 形式で指定してください。'
        );
    }
}
