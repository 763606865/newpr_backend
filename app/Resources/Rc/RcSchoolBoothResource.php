<?php

namespace App\Resources\Rc;

use App\Models\Rc\SchoolBooth;
use App\Resources\Concerns\SerializesOssAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcSchoolBoothResource extends JsonResource
{
    use SerializesOssAttributes;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof SchoolBooth) {
            return (array) $this->resource;
        }

        $image = $this->ossAttributePair('image');

        $data = [
            'id' => $this->resource->id,
            'school_code' => $this->resource->school_code,
            'province_code' => $this->resource->province_code,
            'city_code' => $this->resource->city_code,
            'district_code' => $this->resource->district_code,
            'address' => $this->resource->address,
            'name' => $this->resource->name,
            'image' => $image['path'],
            'display_image' => $image['display'],
            'area_size' => $this->resource->area_size,
            'max_people' => $this->resource->max_people,
            'total_booth_count' => $this->resource->total_booth_count,
            'description' => $this->resource->description,
            'rule' => $this->resource->rule,
            'status' => $this->resource->status->value,
            'status_label' => $this->resource->status->getLabel(),
            'extra' => $this->resource->extra,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];

        if (isset($this->resource->areas_count)) {
            $data['areas_count'] = (int) $this->resource->areas_count;
        }

        if ($this->resource->relationLoaded('areas')) {
            $data['areas'] = RcSchoolBoothAreaResource::collection($this->resource->areas)->resolve($request);
        }

        return $data;
    }
}
