<?php

namespace App\Resources\SApi;

use App\Models\Cms\Announcement;
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
            'city_code' => $this->resource->city_code,
            'title' => $this->resource->title,
            'sub_title' => $this->resource->sub_title,
            'link_url' => $this->resource->link_url,
            'type' => $this->resource->type?->value ?? $this->resource->type,
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
            'extra' => $this->resource->extra,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
