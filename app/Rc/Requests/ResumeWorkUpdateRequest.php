<?php

namespace App\Rc\Requests;

use App\Enums\RcEmploymentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResumeWorkUpdateRequest extends FormRequest
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
            'company_name' => ['sometimes', 'required', 'string', 'max:255'],
            'department' => ['sometimes', 'nullable', 'string', 'max:255'],
            'position' => ['sometimes', 'required', 'string', 'max:255'],
            'employment_type' => ['sometimes', 'nullable', Rule::enum(RcEmploymentType::class)],
            'start_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'is_current' => ['sometimes', 'nullable', 'boolean'],
            'description' => ['sometimes', 'nullable', 'string'],
            'sort' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
            'extra' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
