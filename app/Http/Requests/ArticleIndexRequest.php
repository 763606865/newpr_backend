<?php

namespace App\Http\Requests;

use App\Enums\CmsStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $tagIds = $this->input('tag_ids');

        if (is_string($tagIds) && $tagIds !== '') {
            $this->merge([
                'tag_ids' => array_values(array_filter(array_map('trim', explode(',', $tagIds)))),
            ]);
        }
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'city_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'school_code' => ['sometimes', 'nullable', 'string', 'max:32', Rule::exists('schools', 'school_code')],
            'category_id' => ['sometimes', 'nullable', 'integer', 'min:1', Rule::exists('cms_article_categories', 'id')->whereNull('deleted_at')],
            'category_slug' => ['sometimes', 'nullable', 'string', 'max:128'],
            'keyword' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_recommend' => ['sometimes', 'nullable', 'boolean'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('cms_article_tags', 'id')->where(function ($query): void {
                    $query->whereNull('deleted_at')
                        ->where('status', CmsStatus::Enabled->value);
                }),
            ],
            'tags_match' => ['sometimes', 'string', Rule::in(['all', 'any'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'city_code' => '城市编码',
            'school_code' => '院校代码',
            'category_id' => '分类 ID',
            'category_slug' => '分类别名',
            'keyword' => '关键词',
            'is_recommend' => '推荐资讯',
            'page' => '页码',
            'per_page' => '每页条数',
            'tag_ids' => '标签 ID',
            'tags_match' => '标签匹配方式',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function searchFilters(): array
    {
        return [
            'city_code' => $this->validated('city_code'),
            'school_code' => $this->validated('school_code'),
            'category_id' => $this->validated('category_id'),
            'category_slug' => $this->validated('category_slug'),
            'keyword' => $this->validated('keyword'),
            'is_recommend' => $this->validated('is_recommend'),
            'tag_ids' => $this->tagIds(),
            'tags_match_all' => $this->tagsMatchAll(),
        ];
    }

    /**
     * @return array<int, int>
     */
    public function tagIds(): array
    {
        $tagIds = $this->validated('tag_ids', []);

        if (! is_array($tagIds)) {
            return [];
        }

        return array_values(array_map(static fn (mixed $tagId): int => (int) $tagId, $tagIds));
    }

    public function tagsMatchAll(): bool
    {
        return ($this->validated('tags_match') ?? 'all') !== 'any';
    }
}
