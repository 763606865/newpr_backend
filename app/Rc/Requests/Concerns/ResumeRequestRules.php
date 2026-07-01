<?php

namespace App\Rc\Requests\Concerns;

use App\Enums\RcPoliticalStatus;
use Illuminate\Validation\Rule;

trait ResumeRequestRules
{
    use ValidatesCityAreaCodes;

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function resumeFieldRules(bool $sometimes = false): array
    {
        $rule = function (array $rules) use ($sometimes): array {
            if (! $sometimes) {
                return $rules;
            }

            return array_merge(['sometimes'], $rules);
        };

        return array_merge([
            'title' => $rule(['nullable', 'string', 'max:255']),
            'full_name' => $rule(['required', 'string', 'max:50']),
            'avatar' => $rule(['nullable', 'string', 'max:255']),
            'gender' => $rule(['required', 'integer', 'min:0', 'max:2']),
            'nation' => $rule(['nullable', 'string', 'max:20']),
            'birth_date' => $rule(['required', 'date_format:Y-m-d']),
            'marital_status' => $rule(['nullable', 'integer', 'min:0', 'max:4']),
            'political_status' => $rule(['nullable', 'integer', Rule::enum(RcPoliticalStatus::class)]),
            'current_identity' => $rule(['nullable', 'integer', 'min:0']),
            'work_start_date' => $rule(['nullable', 'date_format:Y-m-d']),
            'work_years' => $rule(['nullable', 'integer', 'min:0', 'max:80']),
            'current_salary' => $rule(['nullable', 'string', 'max:50']),
            'salary_remark' => $rule(['nullable', 'string', 'max:200']),
            'recruit_source' => $rule(['nullable', 'string', 'max:100']),
            'highest_education_level' => $rule(['nullable', 'integer', 'min:1', 'max:6']),
            'is_fresh_graduate' => $rule(['nullable', 'boolean']),
            'expected_salary_min' => $rule(['nullable', 'numeric', 'min:0']),
            'expected_salary_max' => $rule(['nullable', 'numeric', 'min:0']),
            'expected_salary_unit' => $rule(['nullable', Rule::in([1, 2, 3])]),
            'household_register_detail' => $rule(['nullable', 'string', 'max:200']),
            'current_residence_detail' => $rule(['nullable', 'string', 'max:200']),
            'residence_country' => $rule(['nullable', 'string', 'max:50']),
            'phone' => $rule(['required', 'string', 'max:20']),
            'email' => $rule(['required', 'email:rfc', 'max:100']),
            'is_primary' => $rule(['nullable', 'boolean']),
        ], $this->cityAreaCodeFieldRules($sometimes));
    }

    /**
     * @return array<string, string>
     */
    protected function resumeFieldAttributes(): array
    {
        return [
            'title' => '简历名称',
            'full_name' => '姓名',
            'avatar' => '头像',
            'gender' => '性别',
            'id_card' => '身份证号',
            'nation' => '民族',
            'birth_date' => '出生日期',
            'marital_status' => '婚姻状况',
            'political_status' => '政治面貌',
            'native_place' => '籍贯',
            'current_identity' => '当前身份',
            'work_start_date' => '参加工作日期',
            'work_years' => '工作年限',
            'current_salary' => '当前/期望薪资',
            'salary_remark' => '薪资备注',
            'recruit_source' => '招聘信息获取来源',
            'highest_education_level' => '最高学历',
            'is_fresh_graduate' => '是否应届生',
            'expected_salary_min' => '期望薪资下限',
            'expected_salary_max' => '期望薪资上限',
            'expected_salary_unit' => '期望薪资单位',
            'household_register' => '户口所在地',
            'household_register_detail' => '户口所在地详细地址',
            'current_city_code' => '现居住城市编码',
            'current_residence_detail' => '现居住地详细地址',
            'residence_country' => '现居住国家/地区',
            'phone' => '联系电话',
            'email' => '电子邮箱',
            'is_primary' => '是否主简历',
        ];
    }
}
