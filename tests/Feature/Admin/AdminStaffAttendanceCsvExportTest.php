<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffAttendanceCsvExportTest extends TestCase
{
    use RefreshDatabase;

    // 管理者が指定スタッフ・指定月のCSVをダウンロードできる
    public function test_admin_can_download_csv_for_general_user(): void
    {
        $admin = User::factory()->create([
            'name' => '管理者',
            'admin_status' => true,
        ]);

        $targetUser = User::factory()->create([
            'name' => '一般ユーザーA',
            'admin_status' => false,
        ]);

        $otherUser = User::factory()->create([
            'name' => '一般ユーザーB',
            'admin_status' => false,
        ]);

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $targetUser->id,
            'date' => '2026-09-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        AttendanceBreak::factory()->create([
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $targetUser->id,
            'date' => '2026-08-10',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $otherUser->id,
            'date' => '2026-09-10',
            'clock_in' => '09:10:00',
            'clock_out' => '18:10:00',
        ]);

        $response = $this->actingAs($admin)
            ->post('/export', [
                'user_id' => $targetUser->id,
                'year_month' => '2026-09',
            ]);

        $response->assertStatus(200);
        $response->assertDownload('attendance.csv');

        $csv = $response->streamedContent();

        $this->assertStringStartsWith(
            "\xEF\xBB\xBF日付,出勤,退勤,休憩,合計",
            $csv
        );
        $this->assertStringContainsString('09/10', $csv);
        $this->assertStringContainsString('09:00', $csv);
        $this->assertStringContainsString('18:00', $csv);
        $this->assertStringContainsString('01:00', $csv);
        $this->assertStringContainsString('08:00', $csv);

        $this->assertStringNotContainsString('08/10', $csv);
        $this->assertStringNotContainsString('09:10', $csv);
        $this->assertStringNotContainsString('18:10', $csv);
    }

    // 管理者のIDを user_id に指定したらCSV出力できない
    public function test_admin_user_cannot_be_selected_for_csv_export(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $response = $this->actingAs($admin)
            ->from('/admin/attendance/staff/1')
            ->post('/export', [
                'user_id' => $admin->id,
                'year_month' => '2026-09',
            ]);

        $response->assertSessionHasErrors('user_id');
    }

    // 不正な形式の年月ではCSV出力できない
    public function test_invalid_year_month_cannot_be_used_for_csv_export(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $targetUser = User::factory()->create([
            'admin_status' => false,
        ]);

        $response = $this->actingAs($admin)
            ->post('/export', [
                'user_id' => $targetUser->id,
                'year_month' => '2026/09',
            ]);

        $response->assertSessionHasErrors('year_month');
    }

    // 存在しないユーザーIDではCSV出力できない
    public function test_nonexistent_user_cannot_be_selected_for_csv_export(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $response = $this->actingAs($admin)
            ->post('/export', [
                'user_id' => 999999,
                'year_month' => '2026-09',
            ]);

        $response->assertSessionHasErrors('user_id');
    }
}
