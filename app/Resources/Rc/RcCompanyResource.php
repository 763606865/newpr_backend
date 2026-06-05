<?php

namespace App\Resources\Rc;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcCompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Company) {
            return (array) $this->resource;
        }

        $data = [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'credit_code' => $this->resource->credit_code,
            'legal_person' => $this->resource->legal_person,
            'contact_phone' => $this->resource->contact_phone,
            'address' => $this->resource->address,
            'status' => $this->resource->status,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];

        if ($this->resource->relationLoaded('licenses')) {
            $data['licenses'] = RcCompanyLicenseResource::collection($this->resource->licenses)->resolve($request);
        }

        if ($this->resource->relationLoaded('contacts')) {
            $data['contacts'] = RcCompanyContactResource::collection($this->resource->contacts)->resolve($request);
        }

        return $data;
    }
}
