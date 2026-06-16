<?php

namespace App\Http\Requests;

use App\Enums\CmsAnnouncementPublisherType;
use App\Enums\CmsStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnnouncementIndexRequest extends FormRequest
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

        $publisherTypes = $this->input('publisher_types');

        if (is_string($publisherTypes) && $publisherTypes !== '') {
            $this->merge([
                'publisher_types' => array_values(array_filter(array_map('trim', explode(',', $publisherTypes)))),
            ]);
        }
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'province_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'city_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'district_code' => ['sometimes', 'nullable', 'string', 'max:32'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('cms_tags', 'id')->where(function ($query): void {
                    $query->whereNull('deleted_at')
                        ->where('status', CmsStatus::Enabled->value);
                }),
            ],
            'tags_match' => ['sometimes', 'string', Rule::in(['all', 'any'])],
            'publisher_types' => ['sometimes', 'array'],
            'publisher_types.*' => [
                'integer',
                'distinct',
                Rule::enum(CmsAnnouncementPublisherType::class),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'province_code' => '省份编码',
            'city_code' => '城市编码',
            'district_code' => '区县编码',
            'page' => '页码',
            'per_page' => '每页条数',
            'tag_ids' => '标签 ID',
            'tags_match' => '标签匹配方式',
            'publisher_types' => '发布人类型',
        ];
    }

    public function regionCode(): ?string
    {
        $provinceCode = $this->validated('province_code');
        $cityCode = $this->validated('city_code');
        $districtCode = $this->validated('district_code');

        if (is_string($districtCode) && $districtCode !== '') {
            return $districtCode;
        }

        if (is_string($cityCode) && $cityCode !== '') {
            return $cityCode;
        }

        if (is_string($provinceCode) && $provinceCode !== '') {
            return $provinceCode;
        }

        return null;
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

    /**
     * @return array<int, int>
     */
    public function publisherTypes(): array
    {
        $publisherTypes = $this->validated('publisher_types', []);

        if (! is_array($publisherTypes)) {
            return [];
        }

        return array_values(array_map(static fn (mixed $publisherType): int => (int) $publisherType, $publisherTypes));
    }
}
