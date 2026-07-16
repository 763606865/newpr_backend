<?php

namespace App\Rc\Requests;

use App\Enums\ImConversationType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImConversationStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ImConversationType::class)],
            'subject' => ['nullable', 'string', 'max:255'],
            'members' => ['sometimes', 'array'],
            'members.*.external_user_id' => ['required', 'string', 'max:64'],
        ];
    }
}
