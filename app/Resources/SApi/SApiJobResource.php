<?php

namespace App\Resources\SApi;

use App\Enums\RcEducationLevel;
use App\Models\Rc\Job;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SApiJobResource extends JsonResource
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
            'employment_type' => $this->resource->employment_type?->value ?? $this->resource->employment_type,
            'city_code' => $this->resource->city_code,
            'workplace' => $this->resource->workplace,
            'salary_min' => $this->resource->salary_min,
            'salary_max' => $this->resource->salary_max,
            'salary_unit' => $this->resource->salary_unit?->value ?? $this->resource->salary_unit,
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
            'status' => $this->resource->status?->value ?? $this->resource->status,
            'published_at' => $this->resource->published_at,
            'expired_at' => $this->resource->expired_at,
            'keywords' => $extra['keywords'] ?? [],
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
            $data['position'] = [
                'code' => $this->resource->position->code,
                'name' => $this->resource->position->name,
            ];
        }

        return $data;
    }
}
