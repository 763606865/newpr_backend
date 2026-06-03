<?php

namespace App\Rc\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResumeUpdateRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'full_name' => ['sometimes', 'required', 'string', 'max:50'],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'gender' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:2'],
            'id_card' => ['sometimes', 'nullable', 'string', 'size:18'],
            'nation' => ['sometimes', 'nullable', 'string', 'max:20'],
            'birth_date' => ['sometimes', 'nullable', 'date'],
            'birth_month' => ['sometimes', 'nullable', 'date_format:Y-m'],
            'age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:120'],
            'marital_status' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:4'],
            'political_status' => ['sometimes', 'nullable', 'string', 'max:20'],
            'native_place' => ['sometimes', 'nullable', 'string', 'max:100'],
            'current_identity' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'work_start_date' => ['sometimes', 'nullable', 'date'],
            'work_years' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:80'],
            'current_salary' => ['sometimes', 'nullable', 'string', 'max:50'],
            'salary_remark' => ['sometimes', 'nullable', 'string', 'max:200'],
            'recruit_source' => ['sometimes', 'nullable', 'string', 'max:100'],
            'highest_education_level' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:6'],
            'is_fresh_graduate' => ['sometimes', 'nullable', 'boolean'],
            'expected_salary_min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'expected_salary_max' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'expected_salary_unit' => ['sometimes', 'nullable', Rule::in([1, 2, 3])],
            'household_register' => ['sometimes', 'nullable', 'string', 'max:100'],
            'household_register_detail' => ['sometimes', 'nullable', 'string', 'max:200'],
            'current_residence_city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'current_city_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'current_residence_detail' => ['sometimes', 'nullable', 'string', 'max:200'],
            'residence_country' => ['sometimes', 'nullable', 'string', 'max:50'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'email' => ['sometimes', 'required', 'email:rfc', 'max:100'],
            'source_type' => ['sometimes', 'nullable', Rule::in([1, 2, 3, 4])],
            'file_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'file_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'file_ext' => ['sometimes', 'nullable', 'string', 'max:16'],
            'text_content' => ['sometimes', 'nullable', 'string'],
            'parsed_data' => ['sometimes', 'nullable', 'array'],
            'is_primary' => ['sometimes', 'nullable', 'boolean'],
            'status' => ['sometimes', 'nullable', Rule::in([0, 1])],
            'extra' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
