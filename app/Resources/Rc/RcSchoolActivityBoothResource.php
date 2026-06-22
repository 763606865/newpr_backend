<?php

namespace App\Resources\Rc;

use App\Models\Rc\SchoolActivityBooth;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcSchoolActivityBoothResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof SchoolActivityBooth) {
            return (array) $this->resource;
        }

        $data = [
            'id' => $this->resource->id,
            'activity_id' => $this->resource->activity_id,
            'booth_id' => $this->resource->booth_id,
            'school_id' => $this->resource->school_id,
            'company_id' => $this->resource->company_id,
            'booth_area_id' => $this->resource->booth_area_id,
            'booth_area_code' => $this->resource->booth_area_code,
            'booth_area_name' => $this->resource->booth_area_name,
            'booth_no' => $this->resource->booth_no,
            'price' => $this->resource->price,
            'start_at' => $this->resource->start_at,
            'end_at' => $this->resource->end_at,
            'status' => $this->resource->status->value,
            'status_label' => $this->resource->status->getLabel(),
            'remark' => $this->resource->remark,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];

        if ($this->resource->relationLoaded('company') && $this->resource->company) {
            $data['company'] = [
                'id' => $this->resource->company->id,
                'name' => $this->resource->company->name,
            ];
        }

        return $data;
    }
}
