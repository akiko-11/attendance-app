<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $totalWorkMinutes = $this->getTotalWorkMinutes();
        $totalBreakMinutes = $this->getTotalBreakMinutes();

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,

            'user' => new UserResource(
                $this->whenLoaded('user')
            ),

            'date' => $this->date->format('Y-m-d'),
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,

            'total_time' => sprintf(
                '%02d:%02d',
                intdiv($totalWorkMinutes, 60),
                $totalWorkMinutes % 60
            ),

            'total_break_time' => sprintf(
                '%02d:%02d',
                intdiv($totalBreakMinutes, 60),
                $totalBreakMinutes % 60
            ),

            'comment' => $this->comment,

            'breaks' => AttendanceBreakResource::collection(
                $this->whenLoaded('breaks')
            ),

            'applications' => ApplicationResource::collection(
                $this->whenLoaded('applications')
            ),
        ];
    }
}
