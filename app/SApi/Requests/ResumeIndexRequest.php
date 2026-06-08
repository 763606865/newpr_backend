<?php

namespace App\SApi\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class ResumeIndexRequest extends PaginatedIndexRequest
{
    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return array_merge($this->paginationRules(), [
            'user_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'city_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'status' => ['sometimes', 'nullable', 'integer', 'in:0,1'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_merge($this->paginationAttributes(), [
            'user_id' => '用户ID',
            'city_code' => '现居城市编码',
            'status' => '简历状态',
        ]);
    }
}
