<?php

namespace App\SApi\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class CompanyIndexRequest extends PaginatedIndexRequest
{
    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return array_merge($this->paginationRules(), [
            'parent_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'status' => ['sometimes', 'nullable', 'integer', 'in:0,1'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_merge($this->paginationAttributes(), [
            'parent_id' => '上级企业ID',
            'status' => '企业状态',
        ]);
    }
}
