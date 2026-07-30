<?php

namespace App\Rc\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WechatBindPhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pending_token' => ['required', 'string', 'size:64'],
            'phone' => ['required', 'regex:/^1[3-9]\d{9}$/'],
            'code' => ['required', 'digits:6'],
        ];
    }
}
