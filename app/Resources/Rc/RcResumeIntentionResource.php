<?php

namespace App\Resources\Rc;

use App\Models\Rc\ResumeIntention;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcResumeIntentionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof ResumeIntention) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'resume_id' => $this->resource->resume_id,
            'user_id' => $this->resource->user_id,
            'job_status' => $this->resource->job_status?->value ?? $this->resource->job_status,
            'employment_type' => $this->resource->employment_type?->value ?? $this->resource->employment_type,
            'expected_city_code' => $this->resource->expected_city_code,
            'expected_industry_codes' => $this->resource->expected_industry_codes,
            'expected_position_id' => $this->resource->expected_position_id,
            'salary_min' => $this->resource->salary_min,
            'salary_max' => $this->resource->salary_max,
            'salary_unit' => $this->resource->salary_unit?->value ?? $this->resource->salary_unit,
            'available_date' => $this->resource->available_date,
            'extra' => $this->resource->extra,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
