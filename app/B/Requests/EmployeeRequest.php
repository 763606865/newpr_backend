<?php

namespace App\B\Requests;

use App\Enums\EmployeeStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
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
        $employeeId = $this->route('employee') ?? $this->route('id');
        $employeeTable = (new Employee)->getTable();

        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists((new User)->getTable(), 'id')->whereNull('deleted_at'),
                Rule::unique($employeeTable, 'user_id')
                    ->where(function ($query) use ($companyId): void {
                        if ($companyId) {
                            $query->where('company_id', $companyId);
                        }
                    })
                    ->whereNull('deleted_at')
                    ->ignore($employeeId),
            ],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists((new Department)->getTable(), 'id')->where(function ($query) use ($companyId): void {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                })->whereNull('deleted_at'),
            ],
            'position_id' => [
                'nullable',
                'integer',
                Rule::exists((new Position)->getTable(), 'id')->where(function ($query) use ($companyId): void {
                    if ($companyId) {
                        $query->where('company_id', $companyId);
                    }
                })->whereNull('deleted_at'),
            ],
            'employee_no' => [
                'nullable',
                'string',
                'max:60',
                Rule::unique($employeeTable, 'employee_no')
                    ->where(function ($query) use ($companyId): void {
                        if ($companyId) {
                            $query->where('company_id', $companyId);
                        }
                    })
                    ->whereNull('deleted_at')
                    ->ignore($employeeId),
            ],
            'real_name' => ['nullable', 'string', 'max:60'],
            'avatar' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:100'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', Rule::enum(EmployeeStatus::class)],
            'entry_time' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->input('status', EmployeeStatus::Active->value),
            'avatar' => $this->input('avatar', ''),
            'real_name' => $this->input('real_name', ''),
        ]);
    }

    public function attributes(): array
    {
        return [
            'user_id' => '关联用户',
            'department_id' => '部门',
            'position_id' => '岗位',
            'employee_no' => '员工工号',
            'real_name' => '员工姓名',
            'avatar' => '头像',
            'email' => '邮箱',
            'mobile' => '手机号',
            'status' => '状态',
            'entry_time' => '加入时间',
        ];
    }
}
