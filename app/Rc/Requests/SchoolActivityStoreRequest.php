<?php

namespace App\Rc\Requests;

use App\Enums\RcSchoolActivityType;
use App\Rc\Requests\Concerns\ValidatesRegionCodes;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolActivityStoreRequest extends FormRequest
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
            'type' => ['nullable', 'integer', Rule::enum(RcSchoolActivityType::class)],
            'title' => ['required', 'string', 'max:255'],
            'cover_image' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'],
            'link_url' => ['nullable', 'string', 'max:500', 'url'],
            'address' => ['nullable', 'string', 'max:200'],
            'register_start_date' => ['nullable', 'date'],
            'register_end_date' => ['nullable', 'date', 'after_or_equal:register_start_date'],
            'start_time' => ['nullable', 'date'],
            'end_time' => ['nullable', 'date', 'after_or_equal:start_time'],
            'contact_name' => ['nullable', 'string', 'max:50'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'is_hot' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer'],
            'files' => ['nullable', 'array', 'max:20'],
            'files.*' => ['string', 'max:500'],
            'extra' => ['nullable', 'array'],
            'remark' => ['nullable', 'string', 'max:2000'],
            'booth_id' => ['required', 'integer', Rule::exists('rc_school_booths', 'id')->whereNull('deleted_at')],
            ...$this->regionCodeRules(),
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
