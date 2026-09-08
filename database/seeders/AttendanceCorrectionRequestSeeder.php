<?php

namespace Database\Seeders;

use App\Models\AttendanceCorrectionRequest;
use App\Models\AttendanceRecord;
use App\Models\ProposalBreak;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendanceCorrectionRequestSeeder extends Seeder
{
    public function run(): void
    {
        $user1 = User::where('email', 'user1@example.com')->firstOrFail();
        $user2 = User::where('email', 'user2@example.com')->firstOrFail();

        $user1Requests = [
            [
                'date' => '2026-09-08',
                'new_clock_in' => '09:10',
                'new_clock_out' => '18:00',
                'break_in' => '12:00',
                'break_out' => '13:00',
                'comment' => '出勤時間修正のため',
                'approval_status' => false,
                'created_at' => '2026-09-08 10:00:00',
            ],
            [
                'date' => '2026-09-07',
                'new_clock_in' => '08:50',
                'new_clock_out' => '18:10',
                'break_in' => '12:00',
                'break_out' => '13:00',
                'comment' => '退勤時間修正のため',
                'approval_status' => true,
                'created_at' => '2026-09-08 09:00:00',
            ],
            [
                'date' => '2026-08-28',
                'new_clock_in' => '09:10',
                'new_clock_out' => '18:10',
                'break_in' => '12:10',
                'break_out' => '13:00',
                'comment' => '休憩時間修正のため',
                'approval_status' => false,
                'created_at' => '2026-09-01 10:00:00',
            ],
            [
                'date' => '2026-08-20',
                'new_clock_in' => '08:50',
                'new_clock_out' => '18:00',
                'break_in' => '12:00',
                'break_out' => '13:00',
                'comment' => '出勤時間修正のため',
                'approval_status' => true,
                'created_at' => '2026-08-21 10:00:00',
            ],
            [
                'date' => '2026-07-15',
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:10',
                'break_in' => '12:00',
                'break_out' => '13:00',
                'comment' => '退勤時間修正のため',
                'approval_status' => false,
                'created_at' => '2026-07-16 10:00:00',
            ],
        ];

        $user2Requests = [
            [
                'date' => '2026-09-08',
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:10',
                'break_in' => '12:00',
                'break_out' => '13:00',
                'comment' => '退勤時間修正のため',
                'approval_status' => true,
                'created_at' => '2026-09-08 11:00:00',
            ],
            [
                'date' => '2026-09-04',
                'new_clock_in' => '09:10',
                'new_clock_out' => '18:00',
                'break_in' => '12:00',
                'break_out' => '13:00',
                'comment' => '出勤時間修正のため',
                'approval_status' => false,
                'created_at' => '2026-09-07 10:00:00',
            ],
            [
                'date' => '2026-08-25',
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'break_in' => '12:10',
                'break_out' => '13:00',
                'comment' => '休憩時間修正のため',
                'approval_status' => true,
                'created_at' => '2026-08-26 10:00:00',
            ],
            [
                'date' => '2026-08-10',
                'new_clock_in' => '09:10',
                'new_clock_out' => '18:00',
                'break_in' => '12:00',
                'break_out' => '13:00',
                'comment' => '出勤時間修正のため',
                'approval_status' => false,
                'created_at' => '2026-08-11 10:00:00',
            ],
            [
                'date' => '2026-07-21',
                'new_clock_in' => '08:50',
                'new_clock_out' => '17:50',
                'break_in' => '12:10',
                'break_out' => '13:00',
                'comment' => '休憩時間修正のため',
                'approval_status' => true,
                'created_at' => '2026-07-22 10:00:00',
            ],
        ];

        $this->createRequests($user1, $user1Requests);
        $this->createRequests($user2, $user2Requests);
    }

    private function createRequests(User $user, array $requests): void
    {
        $dates = array_column($requests, 'date');

        $attendances = AttendanceRecord::where('user_id', $user->id)
            ->whereIn('date', $dates)
            ->get()
            ->keyBy(fn ($attendance) => $attendance->date->toDateString());

        foreach ($requests as $data) {
            $attendance = $attendances[$data['date']];

            $correctionRequest = AttendanceCorrectionRequest::factory()->create([
                'user_id' => $user->id,
                'attendance_record_id' => $attendance->id,
                'new_date' => $attendance->date->toDateString(),
                'new_clock_in' => $data['new_clock_in'],
                'new_clock_out' => $data['new_clock_out'],
                'comment' => $data['comment'],
                'approval_status' => $data['approval_status'],
                'created_at' => $data['created_at'],
                'updated_at' => $data['created_at'],
            ]);

            ProposalBreak::factory()->create([
                'attendance_correction_request_id' => $correctionRequest->id,
                'break_in' => $data['break_in'],
                'break_out' => $data['break_out'],
            ]);

            if (! $data['approval_status']) {
                continue;
            }

            $attendance->update([
                'date' => $data['date'],
                'clock_in' => $data['new_clock_in'],
                'clock_out' => $data['new_clock_out'],
                'comment' => $data['comment'],
            ]);

            $attendance->breaks()->delete();

            $attendance->breaks()->create([
                'break_in' => $data['break_in'],
                'break_out' => $data['break_out'],
            ]);
        }
    }
}
