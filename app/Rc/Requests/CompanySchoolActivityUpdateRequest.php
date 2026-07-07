<?php

namespace App\Rc\Requests;

use App\Enums\RcSchoolActivityMode;
use App\Enums\RcSchoolActivityType;
use App\Rc\Requests\Concerns\ValidatesRegionCodes;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanySchoolActivityUpdateRequest extends FormRequest
{
    use ValidatesRegionCodes;

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
            'type' => [
                'sometimes',
                'required',
                'integer',
                Rule::enum(RcSchoolActivityType::class),
                Rule::in([
                    RcSchoolActivityType::JobFair->value,
                    RcSchoolActivityType::Presentation->value,
                ]),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'cover_image' => ['sometimes', 'nullable', 'string', 'max:500'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'link_url' => ['sometimes', 'nullable', 'string', 'max:500', 'url'],
            'address' => ['sometimes', 'nullable', 'string', 'max:200'],
            'register_start_date' => ['sometimes', 'nullable', 'date'],
            'register_end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:register_start_date'],
            'start_time' => ['sometimes', 'nullable', 'date'],
            'end_time' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_time'],
            'contact_name' => ['sometimes', 'nullable', 'string', 'max:50'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'activity_mode' => ['sometimes', 'nullable', 'integer', Rule::enum(RcSchoolActivityMode::class)],
            'is_hot' => ['sometimes', 'nullable', 'boolean'],
            'sort' => ['sometimes', 'nullable', 'integer'],
            'files' => ['sometimes', 'nullable', 'array', 'max:20'],
            'files.*' => ['string', 'max:500'],
            'extra' => ['sometimes', 'nullable', 'array'],
            'remark' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'school_codes' => ['sometimes', 'required_if:type,'.RcSchoolActivityType::Presentation->value, 'nullable', 'array', 'min:1'],
            'school_codes.*' => ['string', 'distinct', 'max:32', Rule::exists('schools', 'school_code')],
            ...$this->regionCodeRules(sometimes: true),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => '活动类型',
            'school_codes' => '申请入校院校',
            'activity_mode' => '活动模式',
        ];
    }
}
