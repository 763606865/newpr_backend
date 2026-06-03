<?php

namespace App\Resources\Rc;

use App\Models\Rc\ResumeEducation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcResumeEducationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof ResumeEducation) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'resume_id' => $this->resource->resume_id,
            'user_id' => $this->resource->user_id,
            'school_name' => $this->resource->school_name,
            'major' => $this->resource->major,
            'degree' => $this->resource->degree?->value ?? $this->resource->degree,
            'education_type' => $this->resource->education_type,
            'start_date' => $this->resource->start_date,
            'end_date' => $this->resource->end_date,
            'is_current' => $this->resource->is_current,
            'description' => $this->resource->description,
            'sort' => $this->resource->sort,
            'extra' => $this->resource->extra,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
