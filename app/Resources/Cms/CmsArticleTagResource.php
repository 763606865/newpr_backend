<?php

namespace App\Resources\Cms;

use App\Models\Cms\ArticleTag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CmsArticleTagResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof ArticleTag) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'sort' => $this->resource->sort,
        ];
    }
}
