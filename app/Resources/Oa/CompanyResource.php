<?php

namespace App\Resources\Oa;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $departments = $this->whenLoaded(relationship: 'departments', default: $this->resource->departments);
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'departments' => DepartmentResource::collection($departments),
        ];
    }
}
