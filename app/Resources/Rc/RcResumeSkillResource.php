<?php

namespace App\Resources\Rc;

use App\Models\Rc\ResumeSkill;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcResumeSkillResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof ResumeSkill) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'resume_id' => $this->resource->resume_id,
            'user_id' => $this->resource->user_id,
            'skill_name' => $this->resource->skill_name,
            'proficiency' => $this->resource->proficiency?->value ?? $this->resource->proficiency,
            'proficiency_label' => $this->resource->proficiency?->getLabel(),
            'description' => $this->resource->description,
            'sort' => $this->resource->sort,
            'extra' => $this->resource->extra,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
