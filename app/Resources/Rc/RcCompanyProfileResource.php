<?php

namespace App\Resources\Rc;

use App\Enums\CompanyBenefitTag;
use App\Models\CompanyProfile;
use App\Resources\Concerns\SerializesOssAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcCompanyProfileResource extends JsonResource
{
    use SerializesOssAttributes;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof CompanyProfile) {
            return (array) $this->resource;
        }

        $logo = $this->ossAttributePair('logo');

        return [
            'short_name' => $this->resource->short_name,
            'logo' => $logo['path'],
            'display_logo' => $logo['display'],
            'city_code' => $this->resource->city_code,
            'scale_type' => $this->resource->scale_type?->value,
            'scale_type_label' => $this->resource->scale_type?->getLabel(),
            'nature_type' => $this->resource->nature_type?->value,
            'nature_type_label' => $this->resource->nature_type?->getLabel(),
            'industry_codes' => $this->resource->industry_codes ?? [],
            'founded_at' => $this->resource->founded_at?->toDateString(),
            'website' => $this->resource->website,
            'introduction' => $this->resource->introduction,
            'benefit_tags' => $this->resource->benefit_tags ?? [],
            'benefit_tag_labels' => $this->benefitTagLabels($this->resource->benefit_tags ?? []),
            'funding_stage' => $this->resource->funding_stage?->value,
            'funding_stage_label' => $this->resource->funding_stage?->getLabel(),
            'profile_status' => $this->resource->profile_status->value,
            'profile_status_label' => $this->resource->profile_status->getLabel(),
            'is_brand' => $this->resource->is_brand,
            'brand_sort' => $this->resource->brand_sort,
            'extra' => $this->resource->extra,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }

    /**
     * @param  array<int, string>  $tags
     * @return array<int, string>
     */
    private function benefitTagLabels(array $tags): array
    {
        return collect($tags)
            ->map(static fn (string $tag): ?string => CompanyBenefitTag::tryFrom($tag)?->getLabel())
            ->filter()
            ->values()
            ->all();
    }
}
