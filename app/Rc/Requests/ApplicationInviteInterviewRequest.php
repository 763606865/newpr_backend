<?php

namespace App\Rc\Requests;

use App\Enums\RcInterviewMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplicationInviteInterviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'interview_at' => ['required', 'date', 'after:now'],
            'mode' => ['required', Rule::enum(RcInterviewMode::class)],
            'interviewer_user_id' => ['nullable', 'integer'],
            'interviewer_name' => ['nullable', 'string', 'max:50'],
            'duration_mins' => ['nullable', 'integer', 'min:1', 'max:480'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_url' => ['nullable', 'string', 'max:255', 'url'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'interview_at' => '面试时间',
            'mode' => '面试方式',
            'interviewer_user_id' => '面试官用户 ID',
            'interviewer_name' => '面试官姓名',
            'duration_mins' => '面试时长',
            'location' => '面试地点',
            'meeting_url' => '会议链接',
            'note' => '备注',
        ];
    }
}
