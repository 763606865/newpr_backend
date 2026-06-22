<?php

namespace App\Resources\Rc;

use App\Models\Rc\SchoolBoothArea;
use App\Resources\Concerns\SerializesOssAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcSchoolBoothAreaResource extends JsonResource
{
    use SerializesOssAttributes;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof SchoolBoothArea) {
            return (array) $this->resource;
        }

        $mapImage = $this->ossAttributePair('map_image');

        return [
            'id' => $this->resource->id,
            'booth_id' => $this->resource->booth_id,
            'code' => $this->resource->code,
            'name' => $this->resource->name,
            'area_size' => $this->resource->area_size,
            'max_people' => $this->resource->max_people,
            'map_image' => $mapImage['path'],
            'display_map_image' => $mapImage['display'],
            'start_no' => $this->resource->start_no,
            'end_no' => $this->resource->end_no,
            'total_booth_count' => $this->resource->total_booth_count,
            'max_company_count' => $this->resource->max_company_count,
            'extra' => $this->resource->extra,
            'sort' => $this->resource->sort,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
