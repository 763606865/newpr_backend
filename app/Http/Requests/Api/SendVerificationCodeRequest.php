<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendVerificationCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['phone', 'email'])],
            'account' => ['required', 'string', 'max:100'],
            'scene' => ['required', 'string', Rule::in(['login', 'forgot_password'])],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => '验证码类型仅支持手机号或邮箱。',
            'scene.in' => '验证码场景不支持。',
        ];
    }
}
