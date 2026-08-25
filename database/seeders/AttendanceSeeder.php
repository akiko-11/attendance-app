<?php

namespace Database\Seeders;

use App\Models\AttendanceBreak;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::whereIn('email', [
            'user1@example.com',
            'user2@example.com',
            'user3@example.com',
        ])->get();

        $dates = [];
        $date = now()->subDay();

        // 直近の平日20日を作成
        while (count($dates) < 20) {
            if (! $date->isWeekend()) {
                $dates[] = $date->copy();
            }

            $date->subDay();
        }

        // 各ユーザーについて20日を繰り返し
        foreach ($users as $user) {
            foreach ($dates as $index => $date) {

                // 勤怠の基本属性を設定
                $attributes = [
                    'user_id' => $user->id,
                    'date' => $date->toDateString(),
                ];

                // 通常勤怠（09:00〜18:00）からの変更①
                if (in_array($index, [4, 14], true)) {
                    $attributes['clock_in'] = '08:50';
                    $attributes['clock_out'] = '17:50';
                }

                // 通常勤怠（09:00〜18:00）からの変更②
                if (in_array($index, [9, 19], true)) {
                    $attributes['clock_in'] = '09:10';
                    $attributes['clock_out'] = '18:10';
                }

                $attendanceRecord = AttendanceRecord::factory()->create($attributes);

                // 作成した勤怠に休憩時間を紐づけ
                AttendanceBreak::factory()->create([
                    'attendance_record_id' => $attendanceRecord->id,
                ]);
            }
        }
    }
}
