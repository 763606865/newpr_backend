<?php

namespace App\Rc\Requests;

use App\Enums\RcSkillProficiency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResumeSkillUpdateRequest extends FormRequest
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
            'skill_name' => ['sometimes', 'required', 'string', 'max:100'],
            'proficiency' => ['sometimes', 'nullable', Rule::enum(RcSkillProficiency::class)],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sort' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
            'extra' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
