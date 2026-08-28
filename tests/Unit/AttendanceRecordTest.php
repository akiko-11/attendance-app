<?php

namespace Tests\Unit;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use PHPUnit\Framework\TestCase;

class AttendanceRecordTest extends TestCase
{
    // 休憩時間の合計が計算できる
    public function test_total_break_minutes_can_be_calculated(): void
    {
        // 勤怠と休憩データを準備
        $attendanceRecord = new AttendanceRecord;

        $break = new AttendanceBreak([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $attendanceRecord->setRelation('breaks', collect([
            $break,
        ]));

        // 休憩時間の合計を取得
        $totalBreakMinutes = $attendanceRecord->getTotalBreakMinutes();

        // 期待する休憩時間と一致する
        $this->assertSame(60, $totalBreakMinutes);
    }

    // 複数回の休憩を合計できる
    public function test_multiple_break_minutes_can_be_calculated(): void
    {
        // 勤怠と複数の休憩データを準備
        $attendanceRecord = new AttendanceRecord;

        $firstBreak = new AttendanceBreak([
            'break_in' => '12:00:00',
            'break_out' => '12:45:00',
        ]);

        $secondBreak = new AttendanceBreak([
            'break_in' => '15:00:00',
            'break_out' => '15:15:00',
        ]);

        $attendanceRecord->setRelation('breaks', collect([
            $firstBreak,
            $secondBreak,
        ]));

        // 休憩時間の合計を取得
        $totalBreakMinutes = $attendanceRecord->getTotalBreakMinutes();

        // 複数回の休憩時間が合計される
        $this->assertSame(60, $totalBreakMinutes);
    }

    public function test_unfinished_break_is_not_included_in_total_break_minutes(): void
    {
        $attendanceRecord = new AttendanceRecord;

        $completedBreak = new AttendanceBreak([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $unfinishedBreak = new AttendanceBreak([
            'break_in' => '15:00:00',
            'break_out' => null,
        ]);

        $attendanceRecord->setRelation('breaks', collect([
            $completedBreak,
            $unfinishedBreak,
        ]));

        $totalBreakMinutes = $attendanceRecord->getTotalBreakMinutes();

        $this->assertSame(60, $totalBreakMinutes);
    }

    // 勤務時間の合計が計算できる
    public function test_total_work_minutes_can_be_calculated(): void
    {
        // 勤怠データを準備
        $attendanceRecord = new AttendanceRecord([
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
        ]);

        // 休憩データを準備
        $break = new AttendanceBreak([
            'break_in' => '12:00:00',
            'break_out' => '13:00:00',
        ]);

        $attendanceRecord->setRelation('breaks', collect([
            $break,
        ]));

        // 勤務時間の合計を取得
        $totalWorkMinutes = $attendanceRecord->getTotalWorkMinutes();

        // 期待する勤務時間と一致する
        $this->assertSame(480, $totalWorkMinutes);
    }

    // 退勤時刻が未登録の場合、勤務時間を計算しない
    public function test_total_work_minutes_is_zero_when_clock_out_is_null(): void
    {
        $attendanceRecord = new AttendanceRecord([
            'clock_in' => '09:00:00',
            'clock_out' => null,
        ]);

        $attendanceRecord->setRelation('breaks', collect());

        $totalWorkMinutes = $attendanceRecord->getTotalWorkMinutes();

        $this->assertSame(0, $totalWorkMinutes);
    }
}
