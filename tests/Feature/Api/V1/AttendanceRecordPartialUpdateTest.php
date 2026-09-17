<?php

namespace Tests\Feature\Api\V1;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceRecordPartialUpdateTest extends TestCase
{
    use RefreshDatabase;

    // clock_in のみを正常にPATCHできる
    public function test_authenticated_user_can_patch_only_clock_in(): void
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
                'clock_in' => '10:00:00',
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in' => '10:00:00',
            'clock_out' => '18:00:00',
        ]);
    }

    // clock_out のみを正常にPATCHできる
    public function test_authenticated_user_can_patch_only_clock_out(): void
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
                'clock_out' => '17:00:00',
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in' => '09:00:00',
            'clock_out' => '17:00:00',
        ]);
    }

    // clock_out を明示的に null へPATCHできる
    public function test_authenticated_user_can_patch_clock_out_to_null(): void
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
                'clock_out' => null,
            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendanceRecord->id,
            'clock_in' => '09:00:00',
            'clock_out' => null,
        ]);
    }
}
