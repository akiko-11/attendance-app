<?php

namespace Tests\Feature\Admin;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendancePendingUpdateTest extends TestCase
{
    use RefreshDatabase;

    // 承認待ち申請があっても、管理者は勤怠を直接修正できる
    public function test_admin_can_update_attendance_with_pending_application(): void
    {
        $admin = User::factory()->create([
            'admin_status' => true,
        ]);

        $user = User::factory()->create([
            'admin_status' => false,
        ]);

        // 元の勤怠・休憩
        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-09-01',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'comment' => '修正前',
        ]);

        $attendance->breaks()->create([
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        // 一般ユーザーによる承認待ち申請
        $application = $attendance->attendanceCorrectionRequests()->create([
            'user_id' => $user->id,
            'new_date' => '2026-09-01',
            'new_clock_in' => '09:10',
            'new_clock_out' => '18:10',
            'comment' => '申請した修正内容',
            'approval_status' => false,
        ]);

        $proposalBreak = $application->proposalBreaks()->create([
            'break_in' => '12:10',
            'break_out' => '13:10',
        ]);

        // 管理者が申請内容とは異なる値で直接修正
        $response = $this->actingAs($admin)
            ->post("/attendance/{$attendance->id}", [
                'new_clock_in' => '09:20',
                'new_clock_out' => '18:20',
                'new_break_in' => ['12:20'],
                'new_break_out' => ['13:20'],
                'comment' => '管理者による直接修正',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect("/admin/attendance/{$attendance->id}");

        // 正式な勤怠が管理者の入力内容に更新される
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendance->id,
            'user_id' => $user->id,
            'clock_in' => '09:20',
            'clock_out' => '18:20',
            'comment' => '管理者による直接修正',
        ]);

        $this->assertSame(
            '2026-09-01',
            $attendance->refresh()->date->toDateString()
        );

        // 正式な休憩が置き換わる
        $this->assertSame(1, $attendance->breaks()->count());

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:20',
            'break_out' => '13:20',
        ]);

        $this->assertDatabaseMissing('attendance_breaks', [
            'attendance_record_id' => $attendance->id,
            'break_in' => '12:00',
            'break_out' => '13:00',
        ]);

        // 修正申請は承認待ちのままで、申請内容も変わらない
        $application->refresh();

        $this->assertFalse($application->approval_status);
        $this->assertSame('09:10', $application->new_clock_in);
        $this->assertSame('18:10', $application->new_clock_out);
        $this->assertSame('申請した修正内容', $application->comment);

        $this->assertSame(1, $application->proposalBreaks()->count());

        $proposalBreak->refresh();

        $this->assertSame('12:10', $proposalBreak->break_in);
        $this->assertSame('13:10', $proposalBreak->break_out);

        // 管理者の勤怠詳細にも直接修正した内容が表示される
        $detailResponse = $this->get(
            "/admin/attendance/{$attendance->id}"
        );

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('value="09:20"', false);
        $detailResponse->assertSee('value="18:20"', false);
        $detailResponse->assertSee('value="12:20"', false);
        $detailResponse->assertSee('value="13:20"', false);
        $detailResponse->assertSee('管理者による直接修正');
    }
}
