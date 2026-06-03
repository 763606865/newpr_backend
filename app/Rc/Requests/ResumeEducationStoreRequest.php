<?php

namespace App\Rc\Requests;

use App\Enums\RcEducationLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResumeEducationStoreRequest extends FormRequest
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
            'school_name' => ['required', 'string', 'max:255'],
            'major' => ['nullable', 'string', 'max:255'],
            'degree' => ['nullable', Rule::enum(RcEducationLevel::class)],
            'education_type' => ['nullable', Rule::in([1, 2])],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'is_current' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'extra' => ['nullable', 'array'],
        ];
    }
}
