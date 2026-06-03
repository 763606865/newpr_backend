<?php

namespace App\Rc\Requests;

use App\Enums\RcEducationLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResumeEducationUpdateRequest extends FormRequest
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
            'school_name' => ['sometimes', 'required', 'string', 'max:255'],
            'major' => ['sometimes', 'nullable', 'string', 'max:255'],
            'degree' => ['sometimes', 'nullable', Rule::enum(RcEducationLevel::class)],
            'education_type' => ['sometimes', 'nullable', Rule::in([1, 2])],
            'start_date' => ['sometimes', 'required', 'date_format:Y-m-d'],
            'end_date' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'is_current' => ['sometimes', 'nullable', 'boolean'],
            'description' => ['sometimes', 'nullable', 'string'],
            'sort' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
            'extra' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
