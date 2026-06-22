<?php

namespace App\Rc\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolActivityJobSubmitRequest extends FormRequest
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
            'job_ids' => ['required', 'array', 'min:1', 'max:50'],
            'job_ids.*' => ['integer', Rule::exists('rc_jobs', 'id')->whereNull('deleted_at')],
        ];
    }
}
