<?php

namespace App\B\Requests;

use App\Models\Company;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $companyTable = (new Company)->getTable();

        return [
            'name' => ['required', 'string', 'max:255'],
            'credit_code' => ['required', 'string', 'max:255', Rule::unique($companyTable, 'credit_code')->whereNull('deleted_at')->ignore($this->route('company') ?? $this->route('id'))],
            'legal_person' => ['required', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '公司名称',
            'credit_code' => '统一社会信用代码',
            'legal_person' => '法人姓名',
            'contact_phone' => '联系电话',
            'address' => '公司地址',
        ];
    }
}
