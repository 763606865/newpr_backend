<?php

namespace App\Rc\Requests;

use App\Enums\RcSalaryUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplicationSendOfferRequest extends FormRequest
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
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0', 'gte:salary_min'],
            'salary_unit' => ['nullable', Rule::enum(RcSalaryUnit::class)],
            'entry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'expire_date' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'salary_min' => '最低薪资',
            'salary_max' => '最高薪资',
            'salary_unit' => '薪资单位',
            'entry_date' => '入职日期',
            'expire_date' => 'Offer 过期日期',
            'note' => '备注',
        ];
    }
}
