<?php

namespace App\Rc\Requests;

use App\Enums\ImInteractionRequestType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImInteractionRequestStoreRequest extends FormRequest
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
            'conversation_id' => ['required', 'integer', Rule::exists('im_conversations', 'id')],
            'receiver_user_im_id' => ['required', 'integer', Rule::exists('rc_user_ims', 'id')],
            'type' => ['required', 'string', Rule::enum(ImInteractionRequestType::class)],
            'payload' => ['nullable', 'array'],
            'payload.application_id' => [
                Rule::requiredIf(fn (): bool => in_array($this->input('type'), [
                    ImInteractionRequestType::RespondInterviewInvitation->value,
                    ImInteractionRequestType::RespondOffer->value,
                ], true)),
                'integer',
                'min:1',
            ],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'conversation_id' => '会话',
            'receiver_user_im_id' => '接收方',
            'type' => '请求类型',
            'payload' => '请求参数',
            'payload.application_id' => '投递记录',
            'expires_at' => '过期时间',
        ];
    }
}
