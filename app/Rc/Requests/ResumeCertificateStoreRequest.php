<?php

namespace App\Rc\Requests;

use App\Enums\RcCertificateType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResumeCertificateStoreRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'cert_type' => ['nullable', Rule::enum(RcCertificateType::class)],
            'issuer' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['nullable', 'date_format:Y-m-d'],
            'expire_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:issue_date'],
            'cert_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'extra' => ['nullable', 'array'],
        ];
    }
}
