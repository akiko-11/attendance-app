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

    // clock_in のみを更新して既存の clock_out より後になる場合は422になる
    public function test_returns_422_when_patch_clock_in_is_after_existing_clock_out(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

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
                'clock_in' => '19:00:00',
            ]
        );

        $response->assertStatus(422);

        $response->assertJsonPath(
            'errors.clock_out.0',
            '退勤時刻は出勤時刻より後の時刻を指定してください。'
        );

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
    }

    // clock_out のみを更新して既存の clock_in より前になる場合は422になる
    public function test_returns_422_when_patch_clock_out_is_before_existing_clock_in(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

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
                'clock_out' => '08:00:00',
            ]
        );

        $response->assertStatus(422);

        $response->assertJsonPath(
            'errors.clock_out.0',
            '退勤時刻は出勤時刻より後の時刻を指定してください。'
        );

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);
    }
}
