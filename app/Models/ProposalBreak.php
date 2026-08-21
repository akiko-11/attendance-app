<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_correction_request_id',
        'break_in',
        'break_out',
    ];

    public function attendanceCorrectionRequest(): BelongsTo
    {
        return $this->belongsTo(AttendanceCorrectionRequest::class);
    }
}
