<?php

namespace App\Resources\Rc;

use App\Models\Rc\Industry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcIndustryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Industry) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'parent_id' => $this->resource->parent_id,
            'name' => $this->resource->name,
            'code' => $this->resource->code,
            'sort' => $this->resource->sort,
            'extra' => $this->resource->extra,
        ];
    }
}
