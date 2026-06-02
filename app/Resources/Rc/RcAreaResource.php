<?php

namespace App\Resources\Rc;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcAreaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Area) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'code' => $this->resource->code,
            'parent_code' => $this->resource->parent_code,
            'name' => $this->resource->name,
            'level' => $this->resource->level?->value ?? $this->resource->level,
            'type' => $this->resource->type,
        ];
    }
}
