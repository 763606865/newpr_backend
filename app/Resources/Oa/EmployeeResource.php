<?php

namespace App\Resources\Oa;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $position = $this->whenLoaded(relationship: 'position', default: $this->resource->position);
        return [
            'id' => $this->resource->id,
            'employee_no' => $this->resource->employee_no,
            'real_name' => $this->resource->real_name,
            'avatar' => $this->resource->avatar,
            'mobile_mask' => $this->resource->mobile_mask,
            'email' => $this->resource->email_mask,
            'position' => [
                'id' => $position->id ?? null,
                'name' => $position->name ?? null,
            ],
        ];
    }
}
