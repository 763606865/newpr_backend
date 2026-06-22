<?php

namespace App\Rc\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SchoolBoothAreaStoreRequest extends FormRequest
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
            'code' => ['nullable', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:100'],
            'area_size' => ['nullable', 'integer', 'min:0'],
            'max_people' => ['nullable', 'integer', 'min:0'],
            'map_image' => ['nullable', 'string', 'max:500'],
            'start_no' => ['required', 'integer', 'min:1'],
            'end_no' => ['required', 'integer', 'gte:start_no'],
            'max_company_count' => ['nullable', 'integer', 'min:1'],
            'extra' => ['nullable', 'array'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
