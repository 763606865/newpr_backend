<?php

namespace App\Rc\Requests;

use App\Enums\RcSchoolActivityType;
use App\Rc\Requests\Concerns\ValidatesRegionCodes;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolActivityUpdateRequest extends FormRequest
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
            'type' => ['sometimes', 'nullable', 'integer', Rule::enum(RcSchoolActivityType::class)],
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
            'is_hot' => ['sometimes', 'nullable', 'boolean'],
            'sort' => ['sometimes', 'nullable', 'integer'],
            'files' => ['sometimes', 'nullable', 'array', 'max:20'],
            'files.*' => ['string', 'max:500'],
            'extra' => ['sometimes', 'nullable', 'array'],
            'remark' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'booth_id' => ['sometimes', 'required', 'integer', Rule::exists('rc_school_booths', 'id')->whereNull('deleted_at')],
            ...$this->regionCodeRules(sometimes: true),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'booth_id' => '展位模板',
        ];
    }
}
