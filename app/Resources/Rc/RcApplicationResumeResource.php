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

        $this->resource->unsetRelation('works');
        $this->resource->unsetRelation('educations');
        $this->resource->unsetRelation('languages');
        $this->resource->unsetRelation('skills');

        $this->resource->load([
            'works' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
            'educations' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
            'languages' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
            'skills' => static fn ($relation) => $relation->orderByDesc('sort')->orderByDesc('id'),
        ]);

        return array_merge(
            (new RcResumeResource($this->resource))->resolve($request),
            [
                'works' => RcResumeWorkResource::collection($this->resource->works)->resolve($request),
                'educations' => RcResumeEducationResource::collection($this->resource->educations)->resolve($request),
                'languages' => RcResumeLanguageResource::collection($this->resource->languages)->resolve($request),
                'skills' => RcResumeSkillResource::collection($this->resource->skills)->resolve($request),
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
            'works' => $snapshot['works'] ?? [],
            'educations' => $snapshot['educations'] ?? [],
            'languages' => $snapshot['languages'] ?? [],
            'skills' => $snapshot['skills'] ?? [],
        ];
    }
}
