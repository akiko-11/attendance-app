<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceCorrectionFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clockInRules = ['required', 'string', 'date_format:H:i'];

        if ($this->filled('new_clock_out')) {
            // 退勤時刻が入力されている場合、出勤時刻が退勤時刻以前か確認
            $clockInRules[] = 'before_or_equal:new_clock_out';
        }

        $breakInRules = [
            'nullable',
            'string',
            'date_format:H:i',
            'required_with:new_break_out.*',
            'after_or_equal:new_clock_in',
        ];

        if ($this->filled('new_clock_out')) {
            // 退勤時刻が入力されている場合、休憩開始時刻が退勤時刻以前か確認
            $breakInRules[] = 'before_or_equal:new_clock_out';
        }

        $breakOutRules = [
            'nullable',
            'string',
            'date_format:H:i',
            'after_or_equal:new_break_in.*',
        ];

        if ($this->filled('new_clock_out')) {
            // 退勤時刻が入力されている場合、休憩終了時刻が退勤時刻以前か確認
            $breakOutRules[] = 'before_or_equal:new_clock_out';
        }

        return [
            'new_clock_in' => $clockInRules,
            'new_clock_out' => ['nullable', 'string', 'date_format:H:i'],
            'new_break_in' => ['nullable', 'array'],
            'new_break_in.*' => $breakInRules,
            'new_break_out' => ['nullable', 'array'],
            'new_break_out.*' => $breakOutRules,
            'comment' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'new_clock_in.before_or_equal' => '出勤時間もしくは退勤時間が不適切な値です',
            'new_break_in.*.after_or_equal' => '休憩時間が不適切な値です',
            'new_break_in.*.before_or_equal' => '休憩時間が不適切な値です',
            'new_break_out.*.after_or_equal' => '休憩時間が不適切な値です',
            'new_break_out.*.before_or_equal' => '休憩時間もしくは退勤時間が不適切な値です',
            'comment.required' => '備考を記入してください',
        ];
    }
}
