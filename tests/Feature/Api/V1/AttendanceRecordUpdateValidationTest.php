<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordUpdateValidationTest extends TestCase
{
    use RefreshDatabase;

    // 同じユーザーの既存日付へ変更すると422になる
    public function test_returns_422_when_updating_to_duplicated_date(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $target = AttendanceRecord::factory()
            ->for($user)
            ->create([
                'date' => '2026-09-01',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '変更対象',
            ]);

        AttendanceRecord::factory()
            ->for($user)
            ->create([
                'date' => '2026-09-02',
            ]);

        $response = $this->putJson(
            "/api/v1/attendance-records/{$target->id}",
            [
                'date' => '2026-09-02',
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'comment' => '更新後',
            ]
        );

        $response->assertStatus(422);

        $response->assertJsonPath(
            'errors.date.0',
            'この日付の勤怠は既に登録されています。'
        );

        $target->refresh();

        $this->assertSame(
            '2026-09-01',
            $target->date->format('Y-m-d')
        );
    }
}
