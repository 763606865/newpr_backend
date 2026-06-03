<?php

namespace App\Rc\Requests;

use App\Enums\RcEmploymentType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResumeWorkStoreRequest extends FormRequest
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
            'company_name' => ['required', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'employment_type' => ['nullable', Rule::enum(RcEmploymentType::class)],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'extra' => ['nullable', 'array'],
        ];
    }
}
