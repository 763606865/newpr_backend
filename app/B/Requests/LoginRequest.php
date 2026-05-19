<?php

namespace App\B\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:13'],
            'code' => ['required', 'string', 'max:6'],
        ];
    }
}
