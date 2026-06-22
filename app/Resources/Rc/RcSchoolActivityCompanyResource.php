<?php

namespace App\Resources\Rc;

use App\Models\Rc\SchoolActivityCompany;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcSchoolActivityCompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof SchoolActivityCompany) {
            return (array) $this->resource;
        }

        $data = [
            'id' => $this->resource->id,
            'activity_id' => $this->resource->activity_id,
            'company_id' => $this->resource->company_id,
            'activity_booth_id' => $this->resource->activity_booth_id,
            'join_source' => $this->resource->join_source->value,
            'join_source_label' => $this->resource->join_source->getLabel(),
            'apply_status' => $this->resource->apply_status->value,
            'apply_status_label' => $this->resource->apply_status->getLabel(),
            'apply_at' => $this->resource->apply_at,
            'remark' => $this->resource->remark,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];

        if (isset($this->resource->activity_jobs_count)) {
            $data['activity_jobs_count'] = (int) $this->resource->activity_jobs_count;
        }

        if ($this->resource->relationLoaded('company') && $this->resource->company) {
            $data['company'] = (new RcCompanyResource($this->resource->company))->resolve($request);
        }

        if ($this->resource->relationLoaded('activityBooth') && $this->resource->activityBooth) {
            $data['activity_booth'] = (new RcSchoolActivityBoothResource($this->resource->activityBooth))->resolve($request);
        }

        if ($this->resource->relationLoaded('activityJobs')) {
            $data['activity_jobs'] = RcSchoolActivityJobResource::collection($this->resource->activityJobs)->resolve($request);
        }

        return $data;
    }
}
