<?php

namespace App\Rc\Requests;

use App\Enums\RcCertificateType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResumeCertificateUpdateRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'cert_type' => ['sometimes', 'nullable', Rule::enum(RcCertificateType::class)],
            'issuer' => ['sometimes', 'nullable', 'string', 'max:255'],
            'issue_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'expire_date' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:issue_date'],
            'cert_no' => ['sometimes', 'nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string'],
            'sort' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:9999'],
            'extra' => ['sometimes', 'nullable', 'array'],
        ];
    }
}
