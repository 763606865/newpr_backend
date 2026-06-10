<?php

namespace App\Resources\Rc;

use App\Models\Rc\ResumePortfolio;
use App\Resources\Concerns\SerializesOssAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcResumePortfolioResource extends JsonResource
{
    use SerializesOssAttributes;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof ResumePortfolio) {
            return (array) $this->resource;
        }

        $url = $this->ossAttributePair('url');
        $coverUrl = $this->ossAttributePair('cover_url');

        return [
            'id' => $this->resource->id,
            'resume_id' => $this->resource->resume_id,
            'user_id' => $this->resource->user_id,
            'title' => $this->resource->title,
            'type' => $this->resource->type?->value ?? $this->resource->type,
            'type_label' => $this->resource->type?->getLabel(),
            'url' => $url['path'],
            'display_url' => $url['display'],
            'cover_url' => $coverUrl['path'],
            'display_cover_url' => $coverUrl['display'],
            'description' => $this->resource->description,
            'sort' => $this->resource->sort,
            'extra' => $this->resource->extra,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
