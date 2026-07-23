<?php

namespace App\Resources\Rc;

use App\Models\Rc\Resume;
use App\Resources\Concerns\SerializesOssAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcResumePreviewResource extends JsonResource
{
    use SerializesOssAttributes;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Resume) {
            return (array) $this->resource;
        }

        $avatar = $this->ossAttributePair('avatar');

        if ($this->resource->relationLoaded('user')) {
            if ($this->resource->user->relationLoaded('jobseekerIdentity')) {
                $identity = $this->resource->user->jobseekerIdentity;
                $external_user_id = $identity?->external_user_id;
            }
        }

        return [
            'id' => $this->resource->id,
            'resume_no' => $this->resource->resume_no,
            'title' => $this->resource->title,
            'full_name' => $this->resource->full_name,
            'avatar' => $avatar['path'],
            'display_avatar' => $avatar['display'],
            'gender' => $this->resource->gender,
            'nation' => $this->resource->nation,
            'birth_month' => $this->resource->birth_month,
            'age' => $this->resource->age,
            'marital_status' => $this->resource->marital_status?->value ?? $this->resource->marital_status,
            'marital_status_label' => $this->resource->marital_status?->getLabel(),
            'political_status' => $this->resource->political_status?->value ?? $this->resource->political_status,
            'political_status_label' => $this->resource->political_status?->getLabel(),
            'native_place' => $this->resource->native_place,
            'current_identity' => $this->resource->current_identity,
            'work_start_date' => $this->resource->work_start_date,
            'work_years' => $this->resource->work_years,
            'highest_education_level' => $this->resource->highest_education_level,
            'is_fresh_graduate' => $this->resource->is_fresh_graduate,
            'expected_salary_min' => $this->resource->expected_salary_min,
            'expected_salary_max' => $this->resource->expected_salary_max,
            'expected_salary_unit' => $this->resource->expected_salary_unit,
            'household_register' => $this->resource->household_register,
            'current_residence_city' => $this->resource->current_residence_city,
            'current_city_code' => $this->resource->current_city_code,
            'residence_country' => $this->resource->residence_country,
            'source_type' => $this->resource->source_type?->value ?? $this->resource->source_type,
            'is_primary' => $this->resource->is_primary,
            'updated_at' => $this->resource->updated_at,
            'external_user_id' => $external_user_id ?? null,
            'works' => RcResumeWorkResource::collection($this->whenLoaded('works')),
            'educations' => RcResumeEducationResource::collection($this->whenLoaded('educations')),
            'intentions' => RcResumeIntentionResource::collection($this->whenLoaded('intentions')),
            'projects' => RcResumeProjectResource::collection($this->whenLoaded('projects')),
            'trainings' => RcResumeTrainingResource::collection($this->whenLoaded('trainings')),
            'languages' => RcResumeLanguageResource::collection($this->whenLoaded('languages')),
            'skills' => RcResumeSkillResource::collection($this->whenLoaded('skills')),
            'certificates' => RcResumeCertificateResource::collection($this->whenLoaded('certificates')),
            'portfolios' => RcResumePortfolioResource::collection($this->whenLoaded('portfolios')),
        ];
    }
}
