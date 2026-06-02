<?php

namespace App\Rc\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResumeStoreRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'full_name' => ['required', 'string', 'max:50'],
            'gender' => ['nullable', 'integer', 'min:0', 'max:2'],
            'id_card' => ['nullable', 'string', 'size:18'],
            'nation' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'birth_month' => ['nullable', 'date_format:Y-m'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'marital_status' => ['nullable', 'integer', 'min:0', 'max:4'],
            'political_status' => ['nullable', 'string', 'max:20'],
            'native_place' => ['nullable', 'string', 'max:100'],
            'current_identity' => ['nullable', 'integer', 'min:0'],
            'work_start_date' => ['nullable', 'date'],
            'work_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'current_salary' => ['nullable', 'string', 'max:50'],
            'salary_remark' => ['nullable', 'string', 'max:200'],
            'recruit_source' => ['nullable', 'string', 'max:100'],
            'highest_education_level' => ['nullable', 'integer', 'min:1', 'max:6'],
            'is_fresh_graduate' => ['nullable', 'boolean'],
            'expected_salary_min' => ['nullable', 'numeric', 'min:0'],
            'expected_salary_max' => ['nullable', 'numeric', 'min:0'],
            'expected_salary_unit' => ['nullable', Rule::in([1, 2, 3])],
            'household_register' => ['nullable', 'string', 'max:100'],
            'household_register_detail' => ['nullable', 'string', 'max:200'],
            'current_residence_city' => ['nullable', 'string', 'max:100'],
            'current_city_code' => ['nullable', 'string', 'max:32'],
            'current_residence_detail' => ['nullable', 'string', 'max:200'],
            'residence_country' => ['nullable', 'string', 'max:50'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email:rfc', 'max:100'],
            'source_type' => ['nullable', Rule::in([1, 2, 3, 4])],
            'file_url' => ['nullable', 'string', 'max:255'],
            'file_name' => ['nullable', 'string', 'max:255'],
            'file_ext' => ['nullable', 'string', 'max:16'],
            'text_content' => ['nullable', 'string'],
            'parsed_data' => ['nullable', 'array'],
            'is_primary' => ['nullable', 'boolean'],
            'status' => ['nullable', Rule::in([0, 1])],
            'extra' => ['nullable', 'array'],
        ];
    }
}
