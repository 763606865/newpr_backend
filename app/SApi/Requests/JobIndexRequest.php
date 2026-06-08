<?php

namespace App\SApi\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class JobIndexRequest extends PaginatedIndexRequest
{
    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return array_merge($this->paginationRules(), [
            'company_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'city_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'status' => ['sometimes', 'nullable', 'integer', 'in:0,1,2,3,4'],
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_merge($this->paginationAttributes(), [
            'company_id' => '企业ID',
            'city_code' => '工作城市编码',
            'status' => '职位状态',
        ]);
    }
}
