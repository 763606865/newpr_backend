<?php

namespace App\Rc\Requests;

use App\Enums\RcSchoolBoothStatus;
use App\Rc\Requests\Concerns\ValidatesRegionCodes;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolBoothUpdateRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'address' => ['sometimes', 'nullable', 'string', 'max:200'],
            'image' => ['sometimes', 'nullable', 'string', 'max:500'],
            'area_size' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'max_people' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'rule' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'nullable', 'integer', Rule::enum(RcSchoolBoothStatus::class)],
            'extra' => ['sometimes', 'nullable', 'array'],
            ...$this->regionCodeRules(sometimes: true),
        ];
    }
}
