<?php

namespace App\Resources\Rc;

use App\Models\Rc\Resume;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcApplicationResumeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Resume) {
            return self::normalizeStoredSnapshot((array) $this->resource);
        }

        $this->resource->unsetRelation('intentions');
        $this->resource->unsetRelation('works');
        $this->resource->unsetRelation('educations');
        $this->resource->unsetRelation('projects');
        $this->resource->unsetRelation('trainings');
        $this->resource->unsetRelation('languages');
        $this->resource->unsetRelation('skills');
        $this->resource->unsetRelation('certificates');
        $this->resource->unsetRelation('portfolios');

        $this->resource->load([
            'intentions' => static fn ($relation) => $relation->orderByDesc('id'),
            'works' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
            'educations' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
            'projects' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
            'trainings' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
            'languages' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
            'skills' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
            'certificates' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
            'portfolios' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
        ]);

        return array_merge(
            (new RcResumeResource($this->resource))->resolve($request),
            [
                'intentions' => RcResumeIntentionResource::collection($this->resource->intentions)->resolve($request),
                'works' => RcResumeWorkResource::collection($this->resource->works)->resolve($request),
                'educations' => RcResumeEducationResource::collection($this->resource->educations)->resolve($request),
                'projects' => RcResumeProjectResource::collection($this->resource->projects)->resolve($request),
                'trainings' => RcResumeTrainingResource::collection($this->resource->trainings)->resolve($request),
                'languages' => RcResumeLanguageResource::collection($this->resource->languages)->resolve($request),
                'skills' => RcResumeSkillResource::collection($this->resource->skills)->resolve($request),
                'certificates' => RcResumeCertificateResource::collection($this->resource->certificates)->resolve($request),
                'portfolios' => RcResumePortfolioResource::collection($this->resource->portfolios)->resolve($request),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    public static function normalizeStoredSnapshot(array $snapshot): array
    {
        return [
            ...$snapshot,
            'intentions' => $snapshot['intentions'] ?? [],
            'works' => $snapshot['works'] ?? [],
            'educations' => $snapshot['educations'] ?? [],
            'projects' => $snapshot['projects'] ?? [],
            'trainings' => $snapshot['trainings'] ?? [],
            'languages' => $snapshot['languages'] ?? [],
            'skills' => $snapshot['skills'] ?? [],
            'certificates' => $snapshot['certificates'] ?? [],
            'portfolios' => $snapshot['portfolios'] ?? [],
        ];
    }
}
