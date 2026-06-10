<?php

namespace App\Rc\Requests;

use App\Enums\RcPortfolioType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResumePortfolioStoreRequest extends FormRequest
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
            'type' => ['nullable', Rule::enum(RcPortfolioType::class)],
            'url' => ['nullable', 'string', 'max:255'],
            'cover_url' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'extra' => ['nullable', 'array'],
        ];
    }
}
