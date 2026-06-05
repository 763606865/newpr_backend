<?php

namespace App\Rc\Requests;

use App\Enums\RcIdentityType;
use App\Models\Rc\UserIdentity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RefreshTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $userId = $this->user()?->getAuthIdentifier() ?? 0;

        return [
            'identity_id' => [
                'required_without:identity_type',
                'integer',
                Rule::exists((new UserIdentity)->getTable(), 'id')
                    ->where('user_id', $userId),
            ],
            'identity_type' => [
                'required_without:identity_id',
                Rule::enum(RcIdentityType::class),
            ],
        ];
    }
}
