<?php

namespace App\Rc\Requests;

use App\Enums\CmsPublishStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolArticleIndexRequest extends FormRequest
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
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'nullable', 'integer', Rule::enum(CmsPublishStatus::class)],
            'category_id' => ['sometimes', 'nullable', 'integer', 'min:1', Rule::exists('cms_article_categories', 'id')->whereNull('deleted_at')],
            'keyword' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'page' => '页码',
            'per_page' => '每页条数',
            'status' => '状态',
            'category_id' => '分类',
            'keyword' => '关键词',
        ];
    }
}
