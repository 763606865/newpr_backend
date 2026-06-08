<?php

namespace App\SApi\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class UserIndexRequest extends PaginatedIndexRequest
{
    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return array_merge($this->paginationRules(), [
            'status' => ['sometimes', 'nullable', 'string', 'max:32'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_merge($this->paginationAttributes(), [
            'status' => '用户状态',
        ]);
    }
}
