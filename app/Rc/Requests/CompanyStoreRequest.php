<?php

namespace App\Rc\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompanyStoreRequest extends FormRequest
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
            'legal_person' => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'job_title' => ['required', 'string', 'max:50'],
            'licenses_file_path' => ['nullable', 'string', 'max:500'],
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
            'legal_person' => '法人姓名',
            'contact_phone' => '联系电话',
            'address' => '企业地址',
            'job_title' => '岗位名称',
            'licenses_file_path' => '营业执照',
        ];
    }
}
