<?php

namespace App\Resources\Rc;

use App\Models\Rc\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Application) {
            return (array) $this->resource;
        }

        $data = [
            'id' => $this->resource->id,
            'company_id' => $this->resource->company_id,
            'job_id' => $this->resource->job_id,
            'resume_id' => $this->resource->resume_id,
            'candidate_user_id' => $this->resource->candidate_user_id,
            'current_stage_id' => $this->resource->current_stage_id,
            'source_type' => $this->resource->source_type?->value,
            'source_type_label' => $this->resource->source_type?->getLabel(),
            'status' => $this->resource->status?->value,
            'status_label' => $this->resource->status?->getLabel(),
            'applied_at' => $this->resource->applied_at,
            'withdrawn_at' => $this->resource->withdrawn_at,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];

        if ($this->resource->relationLoaded('job') && $this->resource->job) {
            $data['job'] = (new RcJobResource($this->resource->job))->resolve($request);
        }

        if ($this->resource->relationLoaded('resume') && $this->resource->getRelation('resume')) {
            $data['resume'] = (new RcResumeResource($this->resource->getRelation('resume')))->resolve($request);
        } elseif (is_array($this->resource->resume_snapshot) && $this->resource->resume_snapshot !== []) {
            $data['candidate'] = $this->candidateSummary($this->resource->resume_snapshot);

            if ($this->shouldIncludeResumeSnapshot($request)) {
                $data['resume_snapshot'] = $this->resource->resume_snapshot;
            }
        }

        if ($this->resource->relationLoaded('company') && $this->resource->company) {
            $data['company'] = [
                'id' => $this->resource->company->id,
                'name' => $this->resource->company->name,
            ];
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    private function candidateSummary(array $snapshot): array
    {
        return [
            'full_name' => $snapshot['full_name'] ?? null,
            'age' => $snapshot['age'] ?? null,
            'work_years' => $snapshot['work_years'] ?? null,
            'highest_education_level' => $snapshot['highest_education_level'] ?? null,
            'current_residence_city' => $snapshot['current_residence_city'] ?? null,
            'current_city_code' => $snapshot['current_city_code'] ?? null,
        ];
    }

    private function shouldIncludeResumeSnapshot(Request $request): bool
    {
        return $request->route()?->getActionMethod() === 'show';
    }
}
