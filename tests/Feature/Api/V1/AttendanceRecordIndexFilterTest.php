<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecordIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    // user_id で勤怠を絞り込める
    public function test_can_filter_attendance_records_by_user_id(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        AttendanceRecord::factory()
            ->for($userA)
            ->create([
                'date' => '2026-09-01',
            ]);

        AttendanceRecord::factory()
            ->for($userB)
            ->create([
                'date' => '2026-09-02',
            ]);

        $response = $this->getJson(
            "/api/v1/attendance-records?user_id={$userA->id}"
        );

        $response->assertStatus(200);

        $response->assertJsonCount(1, 'data');

        $response->assertJsonPath(
            'data.0.user_id',
            $userA->id
        );
    }

    // date で勤怠を絞り込める
    public function test_can_filter_attendance_records_by_date(): void
    {
        $userA = User::factory()->create();

        AttendanceRecord::factory()
            ->for($userA)
            ->create([
                'date' => '2026-09-01',
            ]);

        AttendanceRecord::factory()
            ->for($userA)
            ->create([
                'date' => '2026-09-02',
            ]);

        $response = $this->getJson(
            '/api/v1/attendance-records?date=2026-09-02'
        );

        $response->assertStatus(200);

        $response->assertJsonCount(1, 'data');

        $response->assertJsonPath(
            'data.0.date',
            '2026-09-02'
        );
    }

    // month で勤怠を絞り込める
    public function test_can_filter_attendance_records_by_month(): void
    {
        $userA = User::factory()->create();

        AttendanceRecord::factory()
            ->for($userA)
            ->create([
                'date' => '2026-08-01',
            ]);

        AttendanceRecord::factory()
            ->for($userA)
            ->create([
                'date' => '2026-09-01',
            ]);

        $response = $this->getJson(
            '/api/v1/attendance-records?month=2026-09'
        );

        $response->assertStatus(200);

        $response->assertJsonCount(1, 'data');

        $response->assertJsonPath(
            'data.0.date',
            '2026-09-01'
        );
    }

    // page と per_page を指定してページネーションできる
    public function test_can_paginate_with_page_and_per_page(): void
    {
        $userA = User::factory()->create();

        AttendanceRecord::factory()
            ->for($userA)
            ->create([
                'date' => '2026-09-01',
            ]);

        AttendanceRecord::factory()
            ->for($userA)
            ->create([
                'date' => '2026-09-02',
            ]);

        AttendanceRecord::factory()
            ->for($userA)
            ->create([
                'date' => '2026-09-03',
            ]);

        $response = $this->getJson(
            '/api/v1/attendance-records?per_page=2&page=2'
        );

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.current_page', 2);
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.total', 3);
    }

    // 日付の新しい順で取得できる
    public function test_attendance_records_are_ordered_by_latest_date(): void
    {
        $userA = User::factory()->create();

        AttendanceRecord::factory()
            ->for($userA)
            ->create([
                'date' => '2026-09-01',
            ]);

        AttendanceRecord::factory()
            ->for($userA)
            ->create([
                'date' => '2026-09-02',
            ]);

        AttendanceRecord::factory()
            ->for($userA)
            ->create([
                'date' => '2026-09-03',
            ]);

        $response = $this->getJson(
            "/api/v1/attendance-records?user_id={$userA->id}"
        );

        $response->assertStatus(200);

        $response->assertJsonCount(3, 'data');

        $response->assertJsonPath(
            'data.0.date',
            '2026-09-03'
        );

        $response->assertJsonPath(
            'data.1.date',
            '2026-09-02'
        );

        $response->assertJsonPath(
            'data.2.date',
            '2026-09-01'
        );
    }
}
