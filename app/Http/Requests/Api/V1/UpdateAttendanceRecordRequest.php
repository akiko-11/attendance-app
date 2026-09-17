<?php

namespace App\Http\Requests\Api\V1;

use App\Models\AttendanceRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $attendanceRecord = $this->route('attendanceRecord');

        return [
            'date' => [
                'sometimes',
                'required',
                'date_format:Y-m-d',
                function ($attribute, $value, $fail) use ($attendanceRecord) {
                    $exists = AttendanceRecord::where(
                        'user_id',
                        $attendanceRecord->user_id
                    )
                        ->where('id', '!=', $attendanceRecord->id)
                        ->whereDate('date', $value)
                        ->exists();

                    if ($exists) {
                        $fail('この日付の勤怠は既に登録されています。');
                    }
                },
            ],
            'clock_in' => [
                'sometimes',
                'required',
                'date_format:H:i:s',
            ],
            'clock_out' => [
                'sometimes',
                'nullable',
                'date_format:H:i:s',
            ],
            'comment' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    /**
     * 更新後の出勤・退勤時刻の整合性を追加検証する。
     *
     * @param  Validator  $validator  バリデーション処理を行うValidator
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $attendanceRecord = $this->route('attendanceRecord');

            // clock_in / clock_out について送信されたか判定
            // 送信されていればリクエスト値、未送信ならDB既存値を使用
            $clockIn = $this->has('clock_in')
                ? $this->input('clock_in')
                : $attendanceRecord->clock_in;

            $clockOut = $this->has('clock_out')
                ? $this->input('clock_out')
                : $attendanceRecord->clock_out;

            if ($clockOut !== null && $clockOut <= $clockIn) {
                $validator->errors()->add(
                    'clock_out',
                    '退勤時刻は出勤時刻より後の時刻を指定してください。'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'date.required' => '勤怠日は必須です。',
            'date.date_format' => '勤怠日は YYYY-MM-DD 形式で指定してください。',

            'clock_in.required' => '出勤時刻は必須です。',
            'clock_in.date_format' => '出勤時刻は HH:MM:SS 形式で指定してください。',

            'clock_out.date_format' => '退勤時刻は HH:MM:SS 形式で指定してください。',

            'comment.max' => '備考は 255 文字以内で入力してください。',
        ];
    }
}
