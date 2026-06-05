<?php

namespace App\Resources\Rc;

use App\Models\Rc\UserIdentity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcIdentityOrganizationItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof UserIdentity) {
            return (array) $this->resource;
        }

        return [
            'identity_id' => $this->resource->id,
            'identity_name' => $this->resource->identity_name,
            'organization_type' => $this->resource->organization_type,
            'organization_id' => $this->resource->organization_id,
            'organization_name' => $this->resource->organization_name,
            'job_title' => $this->resource->job_title,
            'is_default' => $this->resource->is_default,
            'status' => $this->resource->status?->value,
            'organization' => $this->resource->organization
                ? (new RcOrganizationResource($this->resource->organization))->toArray($request)
                : null,
        ];
    }
}
