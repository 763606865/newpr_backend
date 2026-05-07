<?php

namespace App\Resources\Oa;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employees = $this->whenLoaded(relationship: 'employees', default: $this->resource->employees);
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'employees' => EmployeeResource::collection($employees),
        ];
    }
}
