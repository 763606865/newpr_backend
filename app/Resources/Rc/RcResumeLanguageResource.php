<?php

namespace App\Resources\Rc;

use App\Models\Rc\ResumeLanguage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcResumeLanguageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof ResumeLanguage) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'resume_id' => $this->resource->resume_id,
            'user_id' => $this->resource->user_id,
            'language' => $this->resource->language,
            'proficiency' => $this->resource->proficiency?->value ?? $this->resource->proficiency,
            'proficiency_label' => $this->resource->proficiency?->getLabel(),
            'certificate' => $this->resource->certificate,
            'sort' => $this->resource->sort,
            'extra' => $this->resource->extra,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
