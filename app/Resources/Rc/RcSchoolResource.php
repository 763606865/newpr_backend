<?php

namespace App\Resources\Rc;

use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcSchoolResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof School) {
            return (array) $this->resource;
        }

        $attributes = $this->resource->getAttributes();

        return array_filter([
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'school_code' => $attributes['school_code'] ?? $attributes['code'] ?? null,
            'code' => $attributes['code'] ?? null,
            'parent_code' => $attributes['parent_code'] ?? null,
            'province' => $attributes['province'] ?? null,
            'city' => $attributes['city'] ?? null,
            'area' => $attributes['area'] ?? null,
            'address' => $attributes['address'] ?? null,
            'competent_dept' => $attributes['competent_dept'] ?? null,
            'type' => $attributes['type'] ?? null,
            'level' => isset($attributes['level']) ? $this->resource->level?->value : null,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
