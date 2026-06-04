<?php

namespace App\SApi\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AnnouncementIndexRequest extends FormRequest
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
            'city_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'created_from' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s'],
            'created_to' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s', 'after_or_equal:created_from'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'city_code' => '城市编码',
            'created_from' => '创建时间起始',
            'created_to' => '创建时间截止',
            'page' => '页码',
            'per_page' => '每页条数',
        ];
    }
}
