<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdIndexRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:64'],
            'city_code' => ['nullable', 'string', 'max:32'],
        ];
    }

    public function slotCode(): string
    {
        return trim((string) $this->validated('code'));
    }

    public function cityCode(): ?string
    {
        $cityCode = trim((string) ($this->validated('city_code') ?? ''));

        return $cityCode !== '' ? $cityCode : null;
    }
}
