<?php

namespace App\Rc\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WechatAppLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:50'],
            'avatar' => ['nullable', 'string', 'max:500'],
        ];
    }
}
