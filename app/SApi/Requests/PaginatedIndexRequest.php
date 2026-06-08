<?php

namespace App\SApi\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

abstract class PaginatedIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    protected function paginationRules(): array
    {
        return [
            'created_from' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s'],
            'created_to' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s', 'after_or_equal:created_from'],
            'updated_from' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s'],
            'updated_to' => ['sometimes', 'nullable', 'date_format:Y-m-d H:i:s', 'after_or_equal:updated_from'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function paginationAttributes(): array
    {
        return [
            'created_from' => '创建时间起始',
            'created_to' => '创建时间截止',
            'updated_from' => '更新时间起始',
            'updated_to' => '更新时间截止',
            'page' => '页码',
            'per_page' => '每页条数',
        ];
    }
}
