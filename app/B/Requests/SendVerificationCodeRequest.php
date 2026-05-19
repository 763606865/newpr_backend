<?php

namespace App\B\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendVerificationCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return ValidationRule
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:13'],
        ];
    }
}
