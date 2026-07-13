<?php

namespace App\Resources\Rc;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcAreaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Area) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'code' => $this->resource->code,
            'parent_code' => $this->resource->parent_code,
            'level' => $this->resource->level?->value,
            'type' => $this->resource->type,
            'depth' => $this->resource->depth,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
