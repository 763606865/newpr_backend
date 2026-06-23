<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolActivitySchoolInviteRegisterRequest extends FormRequest
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
            'school_code' => ['required', 'string', 'max:32', Rule::exists('schools', 'school_code')],
            'contact_name' => ['required', 'string', 'max:50'],
            'contact_phone' => ['required', 'string', 'max:20'],
            'contact_email' => ['nullable', 'string', 'max:100', 'email'],
            'remark' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'school_code' => '院校代码',
            'contact_name' => '联系人姓名',
            'contact_phone' => '联系电话',
            'contact_email' => '联系邮箱',
            'remark' => '备注',
        ];
    }
}
