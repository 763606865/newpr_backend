<?php

namespace App\Rc\Requests;

use App\Enums\RcLanguageProficiency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResumeLanguageUpdateRequest extends FormRequest
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
            'language' => ['sometimes', 'required', 'string', 'max:50'],
            'proficiency' => ['sometimes', 'nullable', Rule::enum(RcLanguageProficiency::class)],
            'certificate' => ['sometimes', 'nullable', 'string', 'max:100'],
            'sort' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
            'extra' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
