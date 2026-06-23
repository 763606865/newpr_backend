<?php

namespace App\Rc\Requests;

use App\Enums\CmsArticleContentType;
use App\Enums\CmsStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class SchoolArticleFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    protected function articleRules(bool $requireTitle = false): array
    {
        $titleRule = $requireTitle ? ['required', 'string', 'max:255'] : ['sometimes', 'string', 'max:255'];

        return [
            'category_id' => ['sometimes', 'nullable', 'integer', 'min:0', Rule::exists('cms_article_categories', 'id')->whereNull('deleted_at')],
            'city_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'title' => $titleRule,
            'sub_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:191'],
            'cover' => ['sometimes', 'nullable', 'string', 'max:500'],
            'summary' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'content' => ['sometimes', 'nullable', 'string'],
            'content_type' => ['sometimes', 'integer', Rule::enum(CmsArticleContentType::class)],
            'author' => ['sometimes', 'nullable', 'string', 'max:255'],
            'source_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'source_url' => ['sometimes', 'nullable', 'string', 'max:500', 'url'],
            'is_top' => ['sometimes', 'boolean'],
            'is_recommend' => ['sometimes', 'boolean'],
            'seo_keywords' => ['sometimes', 'nullable', 'string', 'max:255'],
            'seo_description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'extra' => ['sometimes', 'nullable', 'array'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('cms_article_tags', 'id')->where(function ($query): void {
                    $query->whereNull('deleted_at')
                        ->where('status', CmsStatus::Enabled->value);
                }),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function articleAttributes(): array
    {
        return [
            'category_id' => '分类',
            'city_code' => '城市编码',
            'title' => '标题',
            'sub_title' => '副标题',
            'slug' => '别名',
            'cover' => '封面图',
            'summary' => '摘要',
            'content' => '正文',
            'content_type' => '正文类型',
            'author' => '作者',
            'source_name' => '来源名称',
            'source_url' => '来源链接',
            'is_top' => '置顶',
            'is_recommend' => '推荐',
            'seo_keywords' => 'SEO关键词',
            'seo_description' => 'SEO描述',
            'extra' => '扩展字段',
            'tag_ids' => '标签',
        ];
    }
}
