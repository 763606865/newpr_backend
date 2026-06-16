<?php

namespace App\Resources\SApi;

use App\Models\Cms\Announcement;
use App\Models\Cms\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SApiAnnouncementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Announcement) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'organization_type' => $this->resource->organization_type,
            'organization_id' => $this->resource->organization_id,
            'publisher_name' => $this->resource->publisher_name,
            'publisher_type' => $this->resource->publisher_type?->value ?? $this->resource->publisher_type,
            'publisher_type_label' => $this->resource->publisher_type?->getLabel(),
            'province_code' => $this->resource->province_code,
            'city_code' => $this->resource->city_code,
            'district_code' => $this->resource->district_code,
            'area_code' => $this->resource->area_code,
            'title' => $this->resource->title,
            'sub_title' => $this->resource->sub_title,
            'link_url' => $this->resource->link_url,
            'type' => $this->resource->type?->value ?? $this->resource->type,
            'type_label' => $this->resource->type?->getLabel(),
            'source_name' => $this->resource->source_name,
            'source_url' => $this->resource->source_url,
            'summary' => $this->resource->summary,
            'content' => $this->resource->content,
            'published_at' => $this->resource->published_at,
            'start_at' => $this->resource->start_at,
            'end_at' => $this->resource->end_at,
            'is_top' => $this->resource->is_top,
            'status' => $this->resource->status?->value ?? $this->resource->status,
            'sort' => $this->resource->sort,
            'read_count' => $this->resource->read_count,
            'files' => $this->resource->files,
            'tags' => $this->resource->relationLoaded('tags')
                ? $this->resource->tags
                    ->map(static fn (Tag $tag): array => (new SApiTagResource($tag))->resolve($request))
                    ->values()
                    ->all()
                : [],
            'extra' => $this->resource->extra,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
