<?php

namespace App\Resources\Rc;

use App\Models\CompanyContact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcCompanyContactResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof CompanyContact) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'company_id' => $this->resource->company_id,
            'contact_type' => $this->resource->contact_type?->value,
            'name' => $this->resource->name,
            'id_card' => $this->resource->id_card,
            'phone' => $this->resource->phone,
            'email' => $this->resource->email,
            'position' => $this->resource->position,
            'share_ratio' => $this->resource->share_ratio,
            'address' => $this->resource->address,
            'is_primary' => $this->resource->is_primary,
            'sort' => $this->resource->sort,
            'status' => $this->resource->status,
            'remark' => $this->resource->remark,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
