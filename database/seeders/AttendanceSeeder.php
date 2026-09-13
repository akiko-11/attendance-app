<?php

namespace Database\Seeders;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // user1 はマイ勤怠レポート確認用
        $user1 = User::where('email', 'user1@example.com')
            ->firstOrFail();

        // user2・user3 は従来の勤怠データを作成
        $normalUsers = User::whereIn('email', [
            'user2@example.com',
            'user3@example.com',
        ])->get();

        $this->createReportAttendance($user1);
        $this->createNormalAttendance($normalUsers);
    }

    // user1 のマイ勤怠レポート確認用データを作成
    private function createReportAttendance(User $user): void
    {
        $now = now();

        // 過去5か月の勤怠データ作成
        // 各月の平日15日について、
        // 09:00〜18:00 / 休憩12:00〜13:00 の通常勤務を作成
        for ($i = 5; $i >= 1; $i--) {
            $month = $now->copy()->subMonths($i);

            $period = CarbonPeriod::create(
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth()
            );

            $workDayCount = 0;

            foreach ($period as $date) {
                // 土日は対象外
                if ($date->isWeekend()) {
                    continue;
                }

                $attendanceRecord = AttendanceRecord::factory()->create([
                    'user_id' => $user->id,
                    'date' => $date->toDateString(),
                    'clock_in' => '09:00',
                    'clock_out' => '18:00',
                ]);

                AttendanceBreak::factory()->create([
                    'attendance_record_id' => $attendanceRecord->id,
                    'break_in' => '12:00',
                    'break_out' => '13:00',
                ]);

                $workDayCount++;

                // 各月15日作成したら次の月へ
                if ($workDayCount >= 15) {
                    break;
                }
            }
        }

        // 当月のデータ作成
        // 通常10日 / 残業3日 / 遅刻2日 /
        // 早退1日 / 長時間労働1日の計17日を作成する。

        $currentMonthPatterns = [
            // 通常勤務 10日
            ...array_fill(0, 10, [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
            ]),

            // 残業 3日
            ...array_fill(0, 3, [
                'clock_in' => '09:00',
                'clock_out' => '20:00',
            ]),

            // 遅刻 2日
            ...array_fill(0, 2, [
                'clock_in' => '09:30',
                'clock_out' => '18:00',
            ]),

            // 早退 1日
            [
                'clock_in' => '09:00',
                'clock_out' => '17:00',
            ],

            // 長時間労働 1日
            [
                'clock_in' => '08:00',
                'clock_out' => '21:00',
            ],
        ];

        $currentMonthPeriod = CarbonPeriod::create(
            $now->copy()->startOfMonth(),
            $now->copy()->endOfMonth()
        );

        $patternIndex = 0;

        foreach ($currentMonthPeriod as $date) {
            // 土日は対象外
            if ($date->isWeekend()) {
                continue;
            }

            // 17件作成したら終了
            if ($patternIndex >= count($currentMonthPatterns)) {
                break;
            }

            $pattern = $currentMonthPatterns[$patternIndex];

            $attendanceRecord = AttendanceRecord::factory()->create([
                'user_id' => $user->id,
                'date' => $date->toDateString(),
                'clock_in' => $pattern['clock_in'],
                'clock_out' => $pattern['clock_out'],
            ]);

            // user1 は全レコード固定休憩 12:00〜13:00
            AttendanceBreak::factory()->create([
                'attendance_record_id' => $attendanceRecord->id,
                'break_in' => '12:00',
                'break_out' => '13:00',
            ]);

            $patternIndex++;
        }
    }

    // user2・user3 の従来の勤怠データを作成
    private function createNormalAttendance($users): void
    {
        $dates = [];

        // 2026年7月1日〜9月8日の平日を作成
        $period = CarbonPeriod::create(
            Carbon::create(2026, 7, 1),
            Carbon::create(2026, 9, 8)
        );

        foreach ($period as $date) {
            if (! $date->isWeekend()) {
                $dates[] = $date->copy();
            }
        }

        // 各ユーザーについて対象期間の平日を繰り返す
        foreach ($users as $user) {
            foreach ($dates as $date) {
                $attributes = [
                    'user_id' => $user->id,
                    'date' => $date->toDateString(),
                ];

                // 通常勤怠（09:00〜18:00）からの変更①
                if (in_array($date->day, [7, 21], true)) {
                    $attributes['clock_in'] = '08:50';
                    $attributes['clock_out'] = '17:50';
                }

                // 通常勤怠（09:00〜18:00）からの変更②
                if (in_array($date->day, [14, 28], true)) {
                    $attributes['clock_in'] = '09:10';
                    $attributes['clock_out'] = '18:10';
                }

                $attendanceRecord = AttendanceRecord::factory()->create(
                    $attributes
                );

                AttendanceBreak::factory()->create([
                    'attendance_record_id' => $attendanceRecord->id,
                ]);
            }
        }
    }
}
