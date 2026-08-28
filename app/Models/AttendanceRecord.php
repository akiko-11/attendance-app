<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'comment',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function attendanceCorrectionRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }

    // 休憩時間の合計を計算するメソッド
    public function getTotalBreakMinutes(): int
    {
        return $this->breaks->sum(function ($break) {

            // 休憩戻り時刻が未登録の場合、計算しない
            if ($break->break_out === null) {
                return 0;
            }

            // 休憩時間 = 休憩戻り時刻 - 休憩入り時刻
            return Carbon::parse($break->break_in)
                ->diffInMinutes(Carbon::parse($break->break_out));

        });
    }

    // 勤務時間の合計を計算するメソッド
    public function getTotalWorkMinutes(): int
    {
        // 退勤時刻が未登録の場合、計算しない
        if ($this->clock_out === null) {
            return 0;
        }

        // 出勤から退勤までの時間を計算
        $totalMinutes = Carbon::parse($this->clock_in)
            ->diffInMinutes(Carbon::parse($this->clock_out));

        $totalBreakMinutes = $this->getTotalBreakMinutes();

        // 勤務時間 = 出勤から退勤までの時間 - 休憩時間
        return $totalMinutes - $totalBreakMinutes;
    }
}
