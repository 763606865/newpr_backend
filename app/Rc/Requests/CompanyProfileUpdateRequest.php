<?php

namespace App\Rc\Requests;

use App\Enums\CompanyBenefitTag;
use App\Enums\CompanyFundingStage;
use App\Enums\CompanyNatureType;
use App\Enums\CompanyScaleType;
use App\Enums\CompanyRestType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyProfileUpdateRequest extends FormRequest
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
            'logo' => ['nullable', 'string', 'max:500'],
            'city_code' => ['nullable', 'string', 'max:32'],
            'scale_type' => ['nullable', 'integer', Rule::enum(CompanyScaleType::class)],
            'nature_type' => ['nullable', 'integer', Rule::enum(CompanyNatureType::class)],
            'industry_codes' => ['nullable', 'array', 'max:10'],
            'industry_codes.*' => ['string', 'max:64', Rule::exists('rc_industries', 'code')],
            'founded_at' => ['nullable', 'date'],
            'website' => ['nullable', 'string', 'max:255', 'url'],
            'introduction' => ['nullable', 'string', 'max:5000'],
            'work_time' => ['nullable', 'string', 'max:100'],
            'rest_type' => ['nullable', 'integer', Rule::enum(CompanyRestType::class)],
            'salary_pay_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'has_overtime_subsidy' => ['nullable', 'boolean'],
            'benefit_tags' => ['nullable', 'array', 'max:20'],
            'benefit_tags.*' => ['string', Rule::enum(CompanyBenefitTag::class)],
            'funding_stage' => ['nullable', 'integer', Rule::enum(CompanyFundingStage::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'short_name' => '企业简称',
            'logo' => '企业 Logo',
            'city_code' => '主办公城市',
            'scale_type' => '公司规模',
            'nature_type' => '公司性质',
            'industry_codes' => '所属行业',
            'founded_at' => '成立日期',
            'website' => '官网',
            'introduction' => '企业简介',
            'work_time' => '工作作息时间',
            'rest_type' => '休息制度',
            'salary_pay_day' => '每月发薪日',
            'has_overtime_subsidy' => '是否有加班补助',
            'benefit_tags' => '福利标签',
            'funding_stage' => '融资阶段',
        ];
    }
}
