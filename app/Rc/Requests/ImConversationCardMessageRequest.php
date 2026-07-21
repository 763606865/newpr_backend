<?php

namespace App\Rc\Requests;

use App\Enums\ImBusinessCardType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImConversationCardMessageRequest extends FormRequest
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
            'card_type' => ['required', 'string', Rule::enum(ImBusinessCardType::class)],
            'title' => ['nullable', 'string', 'max:100'],
            'summary' => ['nullable', 'string', 'max:500'],
            'biz' => ['nullable', 'array'],
            'biz.application_id' => ['nullable', 'integer', 'min:1'],
            'biz.job_id' => ['nullable', 'integer', 'min:1'],
            'biz.resume_id' => ['nullable', 'integer', 'min:1'],
            'biz.interview_id' => ['nullable', 'integer', 'min:1'],
            'biz.offer_id' => ['nullable', 'integer', 'min:1'],
            'biz.report_id' => ['nullable', 'integer', 'min:1'],
            'snapshot' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'card_type' => '卡片类型',
            'title' => '卡片标题',
            'summary' => '卡片摘要',
            'biz' => '业务引用',
            'snapshot' => '展示快照',
            'metadata' => '扩展数据',
        ];
    }
}
