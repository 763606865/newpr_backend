<?php

namespace App\Rc\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolActivityCompanyReviewRequest extends FormRequest
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
            'activity_booth_id' => ['nullable', 'integer', Rule::exists('rc_school_activity_booths', 'id')],
            'remark' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
