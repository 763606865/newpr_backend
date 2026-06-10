<?php

namespace App\Resources\Rc;

use App\Models\Major;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcMajorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Major) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'full_code' => $this->resource->full_code,
            'name' => $this->resource->name,
            'level' => $this->resource->level->value,
            'level_label' => $this->resource->level->getLabel(),
            'parent_code' => $this->resource->parent_code,
            'type' => $this->resource->type->value,
            'type_label' => $this->resource->type->getLabel(),
            'tag' => $this->resource->tag,
            'sort' => $this->resource->sort,
        ];
    }
}
