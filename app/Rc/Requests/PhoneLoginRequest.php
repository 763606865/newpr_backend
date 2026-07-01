<?php

namespace App\Rc\Requests;

use App\Enums\RcIdentityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PhoneLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:30'],
            'code' => ['required', 'digits:6'],
            'rc_user_identity_type' => ['sometimes', Rule::enum(RcIdentityType::class)],
        ];
    }
}
