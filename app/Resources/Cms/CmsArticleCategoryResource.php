<?php

namespace App\Resources\Cms;

use App\Models\Cms\ArticleCategory;
use App\Resources\Concerns\SerializesOssAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CmsArticleCategoryResource extends JsonResource
{
    use SerializesOssAttributes;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof ArticleCategory) {
            return (array) $this->resource;
        }

        $cover = $this->ossAttributePair('cover');

        return [
            'id' => $this->resource->id,
            'parent_id' => $this->resource->parent_id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'cover' => $cover['path'],
            'display_cover' => $cover['display'],
            'description' => $this->resource->description,
            'sort' => $this->resource->sort,
        ];
    }
}
