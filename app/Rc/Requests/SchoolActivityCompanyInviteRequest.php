<?php

namespace App\Rc\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolActivityCompanyInviteRequest extends FormRequest
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
            'company_id' => ['required', Rule::exists('companies', 'id')->whereNull('deleted_at')],
            'activity_booth_id' => ['nullable', 'integer', Rule::exists('rc_school_activity_booths', 'id')],
            'remark' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
