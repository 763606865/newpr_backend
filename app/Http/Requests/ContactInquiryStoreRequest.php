<?php

namespace App\Http\Requests;

use App\Enums\RcContactProduct;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactInquiryStoreRequest extends FormRequest
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
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:50'],
            'phone' => ['required', 'regex:/^1[3-9]\d{9}$/'],
            'company_name' => ['nullable', 'string', 'max:150'],
            'source' => ['nullable', 'string', 'max:100'],
            'product' => ['required', Rule::enum(RcContactProduct::class)],
            'content' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => '姓名或称呼',
            'phone' => '手机号',
            'company_name' => '公司名称',
            'source' => '信息来源',
            'product' => '咨询产品',
            'content' => '申请内容',
        ];
    }
}
