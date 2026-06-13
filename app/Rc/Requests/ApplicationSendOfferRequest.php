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
            'salary' => ['required', 'numeric', 'min:0'],
            'salary_unit' => ['nullable', Rule::enum(RcSalaryUnit::class)],
            'has_probation' => ['nullable', 'boolean'],
            'remuneration_note' => ['nullable', 'string', 'max:5000'],
            'attendance_note' => ['nullable', 'string', 'max:5000'],
            'entry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'expire_date' => ['nullable', 'date', 'after_or_equal:today'],
            'note' => ['nullable', 'string', 'max:1000'],
            'extra' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'salary' => '确认薪资',
            'salary_unit' => '薪资单位',
            'has_probation' => '是否有试用期',
            'remuneration_note' => '薪酬说明',
            'attendance_note' => '考勤说明',
            'entry_date' => '入职日期',
            'expire_date' => 'Offer 过期日期',
            'note' => '备注',
            'extra' => '扩展信息',
        ];
    }
}
