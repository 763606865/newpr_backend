<?php

namespace App\Resources\Rc;

use App\Models\Rc\UserIdentity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcUserIdentityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof UserIdentity) {
            return (array) $this->resource;
        }

        $this->resource->append('has_basic_info');

        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'organization_type' => $this->resource->organization_type,
            'organization_id' => $this->resource->organization_id,
            'identity_type' => $this->resource->identity_type?->value,
            'identity_name' => $this->resource->identity_name,
            'organization_name' => $this->resource->organization_name,
            'job_title' => $this->resource->job_title,
            'is_default' => $this->resource->is_default,
            'status' => $this->resource->status?->value,
            'extra' => $this->resource->extra,
            'has_basic_info' => $this->resource->has_basic_info,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
