<?php

namespace App\Rc\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderPayRequest extends FormRequest
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
            'pay_channel' => ['required', Rule::in(['wechat', 'alipay'])],
            'pay_scene' => ['sometimes', Rule::in(['app', 'mini', 'mp', 'h5', 'web', 'scan'])],
            'openid' => ['nullable', 'string', 'max:128'],
        ];
    }

    public function attributes(): array
    {
        return [
            'pay_channel' => '支付方式',
            'pay_scene' => '支付场景',
            'openid' => '微信 OpenID',
        ];
    }
}
