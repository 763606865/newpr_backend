<?php

namespace App\B\Requests;

use App\Enums\DepartmentType;
use App\Models\Department;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => [
                'nullable',
                'integer',
                'min:0',
                function (string $attribute, mixed $value, Closure $fail) use ($companyId): void {
                    if ((int) $value === 0) {
                        return;
                    }

                    $exists = Department::query()
                        ->where('id', (int) $value)
                        ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                        ->exists();

                    if (! $exists) {
                        $fail('上级部门不存在。');
                    }
                },
            ],
            'type' => ['required', Rule::enum(DepartmentType::class)],
            'sort' => ['nullable', 'integer', 'min:0'],
            'remark' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'parent_id' => $this->input('parent_id', 0),
            'sort' => $this->input('sort', 0),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $departmentId = (string) ($this->route('department') ?? $this->route('id') ?? '');
            $parentId = (string) $this->input('parent_id', '0');

            if ($departmentId !== '' && $parentId !== '0' && $departmentId === $parentId) {
                $validator->errors()->add('parent_id', '上级部门不能是自己。');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'name' => '部门名称',
            'parent_id' => '上级部门',
            'type' => '部门类型',
            'sort' => '排序',
            'remark' => '备注',
        ];
    }
}
