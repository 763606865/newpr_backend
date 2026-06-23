<?php

namespace App\Resources\Cms;

use App\Models\Cms\Article;
use App\Models\Cms\ArticleCategory;
use App\Models\Cms\ArticleTag;
use App\Models\School;
use App\Resources\Concerns\SerializesOssAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CmsArticleResource extends JsonResource
{
    use SerializesOssAttributes;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Article) {
            return (array) $this->resource;
        }

        $cover = $this->ossAttributePair('cover');

        $data = [
            'id' => $this->resource->id,
            'category_id' => $this->resource->category_id,
            'city_code' => $this->resource->city_code,
            'school_code' => $this->resource->school_code,
            'school_name' => $this->resolveSchoolName(),
            'title' => $this->resource->title,
            'sub_title' => $this->resource->sub_title,
            'slug' => $this->resource->slug,
            'cover' => $cover['path'],
            'display_cover' => $cover['display'],
            'summary' => $this->resource->summary,
            'author' => $this->resource->author,
            'source_name' => $this->resource->source_name,
            'is_top' => $this->resource->is_top,
            'is_recommend' => $this->resource->is_recommend,
            'published_at' => $this->resource->published_at,
            'view_count' => $this->resource->view_count,
            'category' => $this->resolveCategory(),
        ];

        if ($this->shouldIncludeDetail($request)) {
            $data = [
                ...$data,
                'source_url' => $this->resource->source_url,
                'content' => $this->resource->content?->content,
                'content_type' => $this->resource->content?->content_type?->value,
                'content_type_label' => $this->resource->content?->content_type?->getLabel(),
                'seo_keywords' => $this->resource->seo_keywords,
                'seo_description' => $this->resource->seo_description,
                'tags' => $this->resolveTags(),
                'created_at' => $this->resource->created_at,
                'updated_at' => $this->resource->updated_at,
            ];
        }

        return $data;
    }

    private function shouldIncludeDetail(Request $request): bool
    {
        if ($request->route()?->named('article.show')) {
            return true;
        }

        return (bool) $request->boolean('with_detail');
    }

    /**
     * @return array{id: int, name: string, slug: string|null}|null
     */
    private function resolveCategory(): ?array
    {
        if (! $this->resource->relationLoaded('category') || ! $this->resource->category instanceof ArticleCategory) {
            return null;
        }

        return [
            'id' => $this->resource->category->id,
            'name' => $this->resource->category->name,
            'slug' => $this->resource->category->slug,
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, slug: string|null}>
     */
    private function resolveTags(): array
    {
        if (! $this->resource->relationLoaded('tags')) {
            return [];
        }

        return $this->resource->tags
            ->map(static fn (ArticleTag $tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])
            ->values()
            ->all();
    }

    private function resolveSchoolName(): ?string
    {
        if (! $this->resource->relationLoaded('school') || ! $this->resource->school instanceof School) {
            return null;
        }

        return $this->resource->school->name;
    }
}
