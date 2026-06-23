<?php

namespace App\Resources\Rc;

use App\Models\Rc\SchoolActivitySchool;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcSchoolActivitySchoolResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof SchoolActivitySchool) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'activity_id' => $this->resource->activity_id,
            'school_id' => $this->resource->school_id,
            'school_code' => $this->resource->school?->school_code,
            'school_name' => $this->resource->school?->name,
            'contact_name' => $this->resource->contact_name,
            'contact_phone' => $this->resource->contact_phone,
            'contact_email' => $this->resource->contact_email,
            'apply_status' => $this->resource->apply_status?->value,
            'apply_status_label' => $this->resource->apply_status?->getLabel(),
            'apply_at' => $this->resource->apply_at,
            'remark' => $this->resource->remark,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
