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

        $data = array_filter([
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'school_code' => $this->resource->school_code,
            'province' => $this->resource->province,
            'city' => $this->resource->city,
            'area' => $this->resource->area,
            'address' => $this->resource->address,
            'competent_dept' => $this->resource->competent_dept,
            'type' => $this->resource->type,
            'remark' => $this->resource->remark,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ], static fn (mixed $value): bool => $value !== null);

        if ($this->resource->relationLoaded('profile') && $this->resource->profile) {
            $data['profile'] = (new RcSchoolProfileResource($this->resource->profile))->resolve($request);
        }

        return $data;
    }
}
