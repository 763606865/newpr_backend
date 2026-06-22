<?php

namespace App\Rc\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompanyLookupRequest extends FormRequest
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
            'credit_code' => ['nullable', 'string', 'size:18', 'required_without:name'],
            'name' => ['nullable', 'string', 'min:2', 'max:255', 'required_without:credit_code'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'credit_code' => '统一社会信用代码',
            'name' => '企业名称',
        ];
    }
}
