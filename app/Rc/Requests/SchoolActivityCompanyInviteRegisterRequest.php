<?php

namespace App\Rc\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SchoolActivityCompanyInviteRegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'credit_code' => ['required', 'string', 'size:18'],
            'contact_phone' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '企业名称',
            'credit_code' => '统一社会信用代码',
            'contact_phone' => '联系电话',
        ];
    }
}
