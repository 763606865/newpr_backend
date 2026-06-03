<?php

namespace App\Rc\Requests;

use App\Enums\RcEmploymentType;
use App\Enums\RcResumeJobStatus;
use App\Enums\RcSalaryUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResumeIntentionUpsertRequest extends FormRequest
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
            'job_status' => ['sometimes', 'nullable', Rule::enum(RcResumeJobStatus::class)],
            'employment_type' => ['sometimes', 'nullable', Rule::enum(RcEmploymentType::class)],
            'expected_city_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'expected_industry_codes' => ['sometimes', 'nullable', 'array', 'max:10'],
            'expected_industry_codes.*' => ['string', 'max:64'],
            'expected_position_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'salary_min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'salary_max' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'salary_unit' => ['sometimes', 'nullable', Rule::enum(RcSalaryUnit::class)],
            'available_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'extra' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
