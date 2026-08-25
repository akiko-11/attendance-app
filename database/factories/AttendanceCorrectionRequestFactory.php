<?php

namespace Database\Factories;

use App\Models\AttendanceCorrectionRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceCorrectionRequest>
 */
class AttendanceCorrectionRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'comment' => '勤務時間修正のため',
            'approval_status' => false,
        ];
    }
}
