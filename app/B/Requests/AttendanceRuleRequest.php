<?php

namespace App\B\Requests;

use App\Enums\AttendanceRuleWorkType;
use App\Models\Oa\AttendanceRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $attendanceRuleId = $this->route('attendance_rule') ?? $this->route('id');

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique((new AttendanceRule)->getTable(), 'code')->whereNull('deleted_at')->ignore($attendanceRuleId),
            ],
            'work_type' => ['nullable', Rule::enum(AttendanceRuleWorkType::class)],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'time_segments' => ['nullable', 'array'],
            'time_segments.*.start' => ['required_with:time_segments.*.end', 'date_format:H:i'],
            'time_segments.*.end' => ['required_with:time_segments.*.start', 'date_format:H:i'],
            'core_start_time' => ['nullable', 'date_format:H:i'],
            'core_end_time' => ['nullable', 'date_format:H:i'],
            'required_work_hours' => ['nullable', 'numeric', 'min:0'],
            'is_overnight' => ['nullable', 'boolean'],
            'rest_duration_mins' => ['nullable', 'integer', 'min:0'],
            'late_grace_mins' => ['nullable', 'integer', 'min:0'],
            'early_leave_grace_mins' => ['nullable', 'integer', 'min:0'],
            'clock_in_window_mins' => ['nullable', 'integer', 'min:0'],
            'clock_out_window_mins' => ['nullable', 'integer', 'min:0'],
            'applicable_scope' => ['nullable', 'array'],
            'status' => ['nullable', 'integer', Rule::in([0, 1])],
            'extra' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'work_type' => $this->input('work_type', AttendanceRuleWorkType::Fixed->value),
            'is_overnight' => (int) $this->boolean('is_overnight'),
            'rest_duration_mins' => $this->input('rest_duration_mins', 0),
            'late_grace_mins' => $this->input('late_grace_mins', 0),
            'early_leave_grace_mins' => $this->input('early_leave_grace_mins', 0),
            'clock_in_window_mins' => $this->input('clock_in_window_mins', 30),
            'clock_out_window_mins' => $this->input('clock_out_window_mins', 30),
            'status' => $this->input('status', 1),
        ]);
    }

    public function attributes(): array
    {
        return [
            'name' => '规则名称',
            'code' => '规则编码',
            'work_type' => '工作类型',
            'start_time' => '上班时间',
            'end_time' => '下班时间',
            'time_segments' => '时间段配置',
            'core_start_time' => '核心开始时间',
            'core_end_time' => '核心结束时间',
            'required_work_hours' => '要求工作时长',
            'is_overnight' => '是否跨天',
            'rest_duration_mins' => '休息时长',
            'late_grace_mins' => '迟到容忍分钟',
            'early_leave_grace_mins' => '早退容忍分钟',
            'clock_in_window_mins' => '上班打卡窗口',
            'clock_out_window_mins' => '下班打卡窗口',
            'applicable_scope' => '适用范围',
            'status' => '状态',
            'extra' => '扩展字段',
        ];
    }
}
