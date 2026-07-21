<?php

namespace App\Rc\Requests;

use App\Enums\RcReportReasonType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportStoreRequest extends FormRequest
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
            'reportable_type' => ['required', 'string', Rule::in(['job', 'company', 'resume'])],
            'reportable_id' => ['required', 'integer', 'min:1'],
            'reason_type' => ['required', 'integer', Rule::enum(RcReportReasonType::class)],
            'reason' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:9'],
            'attachments.*' => ['required', 'string', 'max:500'],
            'extra' => ['nullable', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reportable_type' => '举报对象类型',
            'reportable_id' => '举报对象ID',
            'reason_type' => '举报原因类型',
            'reason' => '举报原因',
            'description' => '举报说明',
            'attachments' => '举报凭证附件',
            'attachments.*' => '举报凭证附件',
            'extra' => '扩展字段',
        ];
    }
}
