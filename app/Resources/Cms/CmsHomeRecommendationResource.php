<?php

namespace App\Resources\Cms;

use App\Enums\CmsHomeRecommendationModuleType;
use App\Models\Cms\HomeRecommendation;
use App\Models\Company;
use App\Models\Rc\Job;
use App\Resources\Concerns\SerializesOssAttributes;
use App\Resources\Rc\RcCompanyDiscoveryResource;
use App\Resources\Rc\RcJobResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CmsHomeRecommendationResource extends JsonResource
{
    use SerializesOssAttributes;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof HomeRecommendation) {
            return (array) $this->resource;
        }

        $coverImage = $this->ossAttributePair('cover_image');
        $moduleType = $this->resource->module_type;

        $payload = [
            'id' => $this->resource->id,
            'module_type' => $moduleType?->value,
            'module_type_label' => $moduleType?->getLabel(),
            'title' => $this->resource->title,
            'cover_image' => $coverImage['path'],
            'display_cover_image' => $coverImage['display'],
            'link_url' => $this->resource->link_url,
            'city_code' => $this->resource->city_code,
            'sort' => $this->resource->sort,
            'start_at' => $this->resource->start_at,
            'end_at' => $this->resource->end_at,
        ];

        if ($moduleType instanceof CmsHomeRecommendationModuleType && $moduleType->isJobModule()) {
            $job = $this->resource->recommendable;

            if ($job instanceof Job) {
                $payload['job'] = (new RcJobResource($job))->resolve($request);
                $payload['title'] = filled($this->resource->title) ? $this->resource->title : $job->title;
            }
        }

        if ($moduleType === CmsHomeRecommendationModuleType::FamousCompany) {
            $company = $this->resource->recommendable;

            if ($company instanceof Company) {
                $payload['company'] = (new RcCompanyDiscoveryResource($company))->resolve($request);
                $payload['title'] = filled($this->resource->title)
                    ? $this->resource->title
                    : ($company->profile?->short_name ?: $company->name);
            }
        }

        return $payload;
    }
}
