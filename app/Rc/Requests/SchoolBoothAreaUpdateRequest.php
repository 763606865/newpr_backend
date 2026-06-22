<?php

namespace App\Rc\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SchoolBoothAreaUpdateRequest extends FormRequest
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
            'code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'area_size' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_people' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'map_image' => ['sometimes', 'nullable', 'string', 'max:500'],
            'start_no' => ['sometimes', 'required', 'integer', 'min:1'],
            'end_no' => ['sometimes', 'required', 'integer', 'gte:start_no'],
            'max_company_count' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'extra' => ['sometimes', 'nullable', 'array'],
            'sort' => ['sometimes', 'nullable', 'integer', 'min:0'],
        ];
    }
}
