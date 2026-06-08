<?php

namespace App\Resources\SApi;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SApiCompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Company) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'parent_id' => $this->resource->parent_id,
            'depth' => $this->resource->depth,
            'name' => $this->resource->name,
            'credit_code' => $this->resource->credit_code,
            'legal_person' => $this->resource->legal_person,
            'contact_phone' => $this->resource->contact_phone,
            'address' => $this->resource->address,
            'status' => $this->resource->status?->value ?? $this->resource->status,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
