<?php

namespace App\Resources\Rc;

use App\Models\Rc\UserCompanyBlacklist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcUserCompanyBlacklistResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof UserCompanyBlacklist) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'user_id' => $this->resource->user_id,
            'company_id' => $this->resource->company_id,
            'remark' => $this->resource->remark,
            'company' => $this->resource->relationLoaded('company') && $this->resource->company
                ? (new RcCompanyResource($this->resource->company))->resolve($request)
                : null,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
