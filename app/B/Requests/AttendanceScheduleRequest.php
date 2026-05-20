<?php

namespace App\B\Requests;

use App\Enums\AttendanceRuleWorkType;
use App\Enums\AttendanceScheduleStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Oa\AttendanceRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceScheduleRequest extends FormRequest
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
        $companyId = $this->user('b')?->token()?->responsible_id;

        return [
            'department_id' => [
                'required',
                'integer',
                Rule::exists((new Department)->getTable(), 'id')->where(function ($query) use ($companyId): void {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                })->whereNull('deleted_at'),
            ],
            'employee_id' => [
                'required',
                'integer',
                Rule::exists((new Employee)->getTable(), 'id')->where(function ($query) use ($companyId): void {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                })->whereNull('deleted_at'),
            ],
            'attendance_rule_id' => [
                'required',
                'integer',
                Rule::exists((new AttendanceRule)->getTable(), 'id')->where(function ($query) use ($companyId): void {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                })->whereNull('deleted_at'),
            ],
            'date' => ['required', 'date'],
            'std_start_time' => ['nullable', 'date'],
            'std_end_time' => ['nullable', 'date'],
            'std_work_hours' => ['nullable', 'numeric', 'min:0'],
            'is_rest_day' => ['nullable', 'boolean'],
            'is_overnight' => ['nullable', 'boolean'],
            'work_type' => ['nullable', Rule::enum(AttendanceRuleWorkType::class)],
            'actual_start_time' => ['nullable', 'date'],
            'actual_end_time' => ['nullable', 'date'],
            'actual_work_hours' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::enum(AttendanceScheduleStatus::class)],
            'late_mins' => ['nullable', 'integer', 'min:0'],
            'early_leave_mins' => ['nullable', 'integer', 'min:0'],
            'absence_mins' => ['nullable', 'integer', 'min:0'],
            'extra' => ['nullable', 'array'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_rest_day' => (int) $this->boolean('is_rest_day'),
            'is_overnight' => (int) $this->boolean('is_overnight'),
            'work_type' => $this->input('work_type', AttendanceRuleWorkType::Fixed->value),
            'actual_work_hours' => $this->input('actual_work_hours', 0),
            'status' => $this->input('status', AttendanceScheduleStatus::Pending->value),
            'late_mins' => $this->input('late_mins', 0),
            'early_leave_mins' => $this->input('early_leave_mins', 0),
            'absence_mins' => $this->input('absence_mins', 0),
        ]);
    }

    public function attributes(): array
    {
        return [
            'department_id' => '部门',
            'employee_id' => '员工',
            'attendance_rule_id' => '考勤规则',
            'date' => '考勤日期',
            'std_start_time' => '标准上班时间',
            'std_end_time' => '标准下班时间',
            'std_work_hours' => '标准工时',
            'is_rest_day' => '是否休息日',
            'is_overnight' => '是否跨天',
            'work_type' => '班次模型',
            'actual_start_time' => '实际最早打卡',
            'actual_end_time' => '实际最晚打卡',
            'actual_work_hours' => '实际工时',
            'status' => '考勤状态',
            'late_mins' => '迟到分钟',
            'early_leave_mins' => '早退分钟',
            'absence_mins' => '缺勤分钟',
            'extra' => '扩展字段',
        ];
    }
}
