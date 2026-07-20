<?php

namespace App\Rc\Requests\Concerns;

use App\Enums\RcEducationLevel;
use App\Enums\RcJobEmploymentType;
use App\Enums\RcJobStatus;
use App\Enums\RcSalaryUnit;
use App\Models\Rc\Position;
use Illuminate\Validation\Rule;

trait JobRequestRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function jobFieldRules(bool $forUpdate = false): array
    {
        $required = $forUpdate ? 'sometimes' : 'required';

        return [
            'title' => [$forUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'employment_type' => [$required, Rule::enum(RcJobEmploymentType::class)],
            'position_code' => [
                $forUpdate ? 'sometimes' : 'required',
                'string',
                'max:64',
                Rule::exists((new Position)->getTable(), 'code')->whereNull('deleted_at'),
            ],
            'description' => ['nullable', 'string', 'max:10000'],
            'requirement' => ['nullable', 'string', 'max:10000'],
            'benefit' => ['nullable', 'string', 'max:10000'],
            'education_level' => ['nullable', Rule::enum(RcEducationLevel::class)],
            'experience_min' => ['nullable', 'integer', 'min:0', 'max:50'],
            'experience_max' => ['nullable', 'integer', 'min:0', 'max:50', 'gte:experience_min'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'salary_unit' => ['nullable', Rule::enum(RcSalaryUnit::class)],
            'annual_salary_months' => ['nullable', 'numeric', 'min:0', 'max:99.9'],
            'city_code' => [
                'nullable',
                'string',
                'max:32',
                Rule::exists('areas', 'code'),
            ],
            'workplace' => ['nullable', 'string', 'max:255'],
            'headcount' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'keywords' => ['nullable', 'array', 'max:20'],
            'keywords.*' => ['string', 'max:50'],
            'show_headcount' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::enum(RcJobStatus::class)],
            'expired_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function jobFieldAttributes(): array
    {
        return [
            'title' => '职位名称',
            'employment_type' => '工作性质',
            'position_code' => '职位类别',
            'description' => '职位描述',
            'requirement' => '职位要求',
            'benefit' => '福利待遇',
            'education_level' => '最低学历',
            'experience_min' => '最低经验年限',
            'experience_max' => '最高经验年限',
            'salary_min' => '最低薪资',
            'salary_max' => '最高薪资',
            'salary_unit' => '薪资单位',
            'annual_salary_months' => '年薪月数',
            'city_code' => '工作城市编码',
            'workplace' => '工作地址',
            'headcount' => '招聘人数',
            'department_id' => '部门',
            'keywords' => '职位关键词',
            'keywords.*' => '职位关键词',
            'show_headcount' => '对求职者展示招聘人数',
            'status' => '状态',
            'expired_at' => '过期时间',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function jobFieldMessages(): array
    {
        return [
            'city_code.exists' => '工作城市编码必须选择有效的市级行政区划。',
            'experience_max.gte' => '最高经验年限不能小于最低经验年限。',
            'salary_max.gte' => '最高薪资不能小于最低薪资。',
        ];
    }
}
