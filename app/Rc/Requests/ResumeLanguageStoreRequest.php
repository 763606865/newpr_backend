<?php

namespace App\Rc\Requests;

use App\Enums\RcLanguageProficiency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResumeLanguageStoreRequest extends FormRequest
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
            'language' => ['required', 'string', 'max:50'],
            'proficiency' => ['nullable', Rule::enum(RcLanguageProficiency::class)],
            'certificate' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'extra' => ['nullable', 'array'],
        ];
    }
}
