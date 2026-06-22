<?php

namespace App\Rc\Requests;

use App\Enums\RcSchoolBoothStatus;
use App\Rc\Requests\Concerns\ValidatesRegionCodes;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolBoothStoreRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:200'],
            'image' => ['nullable', 'string', 'max:500'],
            'area_size' => ['nullable', 'integer', 'min:0'],
            'max_people' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:5000'],
            'rule' => ['nullable', 'array'],
            'status' => ['nullable', 'integer', Rule::enum(RcSchoolBoothStatus::class)],
            'extra' => ['nullable', 'array'],
            ...$this->regionCodeRules(),
        ];
    }
}
