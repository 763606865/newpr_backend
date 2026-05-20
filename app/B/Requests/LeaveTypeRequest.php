<?php

namespace App\B\Requests;

use App\Enums\LeaveTypeDeductionType;
use App\Models\Oa\LeaveType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveTypeRequest extends FormRequest
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
        $leaveTypeTable = (new LeaveType)->getTable();
        $companyId = $this->user('b')?->token()?->responsible_id;
        $leaveTypeId = $this->route('leave_type') ?? $this->route('id');

        return [
            'name' => ['required', 'string', 'max:50'],
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique($leaveTypeTable, 'code')
                    ->where(function ($query) use ($companyId): void {
                        if ($companyId) {
                            $query->where('company_id', $companyId);
                        }
                    })
                    ->whereNull('deleted_at')
                    ->ignore($leaveTypeId),
            ],
            'deduction_type' => ['nullable', Rule::enum(LeaveTypeDeductionType::class)],
            'unit_type' => ['nullable', 'integer', Rule::in([1, 2])],
            'min_duration' => ['nullable', 'numeric', 'min:0.1'],
            'need_attachment' => ['nullable', 'boolean'],
            'allow_negative' => ['nullable', 'boolean'],
            'max_continuous_days' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'integer', Rule::in([0, 1])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'deduction_type' => $this->input('deduction_type', LeaveTypeDeductionType::Full->value),
            'unit_type' => $this->input('unit_type', 1),
            'min_duration' => $this->input('min_duration', 0.5),
            'need_attachment' => (int) $this->boolean('need_attachment'),
            'allow_negative' => (int) $this->boolean('allow_negative'),
            'status' => $this->input('status', 1),
        ]);
    }

    public function attributes(): array
    {
        return [
            'name' => '假期名称',
            'code' => '假期编码',
            'deduction_type' => '扣薪类型',
            'unit_type' => '请假单位',
            'min_duration' => '最小请假时长',
            'need_attachment' => '必须附件',
            'allow_negative' => '允许透支',
            'max_continuous_days' => '最大连续请假天数',
            'status' => '状态',
        ];
    }
}
