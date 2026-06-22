<?php

namespace App\Resources\Rc;

use App\Models\Rc\SchoolActivityJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcSchoolActivityJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof SchoolActivityJob) {
            return (array) $this->resource;
        }

        $data = [
            'id' => $this->resource->id,
            'activity_id' => $this->resource->activity_id,
            'company_id' => $this->resource->company_id,
            'school_activity_company_id' => $this->resource->school_activity_company_id,
            'job_id' => $this->resource->job_id,
            'audit_status' => $this->resource->audit_status->value,
            'audit_status_label' => $this->resource->audit_status->getLabel(),
            'reject_reason' => $this->resource->reject_reason,
            'audit_at' => $this->resource->audit_at,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];

        if ($this->resource->relationLoaded('job') && $this->resource->job) {
            $data['job'] = (new RcJobResource($this->resource->job))->resolve($request);
        }

        if ($this->resource->relationLoaded('company') && $this->resource->company) {
            $data['company'] = (new RcCompanyResource($this->resource->company))->resolve($request);
        }

        return $data;
    }
}
