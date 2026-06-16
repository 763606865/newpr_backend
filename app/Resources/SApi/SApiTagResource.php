<?php

namespace App\Resources\SApi;

use App\Models\Cms\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SApiTagResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Tag) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'category' => $this->resource->category,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
        ];
    }
}
