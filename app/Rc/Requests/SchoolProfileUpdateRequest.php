<?php

namespace App\Rc\Requests;

use App\Enums\RcEducationLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolProfileUpdateRequest extends FormRequest
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
            'short_name' => ['nullable', 'string', 'max:100'],
            'province_code' => ['nullable', 'string', 'max:30'],
            'city_code' => ['nullable', 'string', 'max:30'],
            'district_code' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:200'],
            'contact_name' => ['nullable', 'string', 'max:50'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'contact_email' => ['nullable', 'string', 'email', 'max:100'],
            'qualification_file' => ['nullable', 'string', 'max:500'],
            'competent_dept' => ['nullable', 'string', 'max:50'],
            'education_levels' => ['nullable', 'array', 'max:10'],
            'education_levels.*' => ['integer', Rule::enum(RcEducationLevel::class)],
            'main_education_level' => ['nullable', 'integer', Rule::enum(RcEducationLevel::class)],
            'logo' => ['nullable', 'string', 'max:500'],
            'banner' => ['nullable', 'string', 'max:500'],
            'allow_company_apply_activity' => ['nullable', 'boolean'],
            'allow_company_cooperate_apply' => ['nullable', 'boolean'],
            'intro' => ['nullable', 'string', 'max:10000'],
            'remark' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'short_name' => '学校简称',
            'province_code' => '省份代码',
            'city_code' => '城市代码',
            'district_code' => '区县代码',
            'address' => '地址',
            'contact_name' => '校方对接总负责人',
            'contact_phone' => '联系电话',
            'contact_email' => '就业办邮箱',
            'qualification_file' => '资质证明',
            'competent_dept' => '主管部门',
            'education_levels' => '办学层次',
            'main_education_level' => '主办学层次',
            'logo' => '校徽 Logo',
            'banner' => '首页横幅图',
            'allow_company_apply_activity' => '是否允许企业自主发起进校宣讲申请',
            'allow_company_cooperate_apply' => '是否开放校企对接申请入口',
            'intro' => '院校简介',
            'remark' => '备注',
        ];
    }
}
