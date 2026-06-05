<?php

namespace App\Resources\Rc;

use App\Enums\RcEducationLevel;
use App\Models\Rc\Job;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Job) {
            return (array) $this->resource;
        }

        $extra = $this->resource->extra ?? [];

        $data = [
            'id' => $this->resource->id,
            'company_id' => $this->resource->company_id,
            'department_id' => $this->resource->department_id,
            'position_code' => $this->resource->position_code,
            'creator_user_id' => $this->resource->creator_user_id,
            'code' => $this->resource->code,
            'title' => $this->resource->title,
            'employment_type' => $this->resource->employment_type?->value,
            'employment_type_label' => $this->resource->employment_type?->getLabel(),
            'city_code' => $this->resource->city_code,
            'workplace' => $this->resource->workplace,
            'salary_min' => $this->resource->salary_min,
            'salary_max' => $this->resource->salary_max,
            'salary_unit' => $this->resource->salary_unit?->value,
            'salary_unit_label' => $this->resource->salary_unit?->getLabel(),
            'experience_min' => $this->resource->experience_min,
            'experience_max' => $this->resource->experience_max,
            'education_level' => $this->resource->education_level,
            'education_level_label' => $this->resource->education_level !== null
                ? RcEducationLevel::tryFrom((int) $this->resource->education_level)?->getLabel()
                : null,
            'headcount' => $this->resource->headcount,
            'description' => $this->resource->description,
            'requirement' => $this->resource->requirement,
            'benefit' => $this->resource->benefit,
            'status' => $this->resource->status?->value,
            'status_label' => $this->resource->status?->getLabel(),
            'published_at' => $this->resource->published_at,
            'expired_at' => $this->resource->expired_at,
            'keywords' => $extra['keywords'] ?? [],
            'show_headcount' => (bool) ($extra['show_headcount'] ?? true),
            'extra' => $extra,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];

        if ($this->resource->relationLoaded('company') && $this->resource->company) {
            $data['company'] = [
                'id' => $this->resource->company->id,
                'name' => $this->resource->company->name,
            ];
        }

        if ($this->resource->relationLoaded('position') && $this->resource->position) {
            $data['position'] = (new RcPositionResource($this->resource->position))->resolve($request);
        }

        if ($this->resource->relationLoaded('department') && $this->resource->department) {
            $data['department'] = [
                'id' => $this->resource->department->id,
                'name' => $this->resource->department->name,
            ];
        }

        if ($this->resource->relationLoaded('creator') && $this->resource->creator) {
            $data['creator'] = [
                'id' => $this->resource->creator->id,
                'name' => $this->resource->creator->name,
                'nickname' => $this->resource->creator->nickname,
            ];
        }

        return $data;
    }
}
