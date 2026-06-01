<?php

namespace App\Resources\Cms;

use App\Models\Cms\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CmsMenuResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Menu) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'parent_id' => $this->resource->parent_id,
            'name' => $this->resource->name,
            'code' => $this->resource->code,
            'link_type' => $this->resource->link_type?->value ?? $this->resource->link_type,
            'link_url' => $this->resource->link_url,
            'icon' => $this->resource->icon,
            'image' => $this->resource->image,
            'target' => $this->resource->target?->value ?? $this->resource->target,
            'is_show' => $this->resource->is_show,
            'status' => $this->resource->status?->value ?? $this->resource->status,
            'sort' => $this->resource->sort,
            'start_at' => $this->resource->start_at,
            'end_at' => $this->resource->end_at,
            'extra' => $this->resource->extra,
        ];
    }
}
