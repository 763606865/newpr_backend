<?php

namespace App\Resources\Rc;

use App\Enums\RcEducationLevel;
use App\Models\Cast\AliyunOss;
use App\Models\Rc\SchoolProfile;
use App\Resources\Concerns\SerializesOssAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcSchoolProfileResource extends JsonResource
{
    use SerializesOssAttributes;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof SchoolProfile) {
            return (array) $this->resource;
        }

        $logo = $this->ossAttributePair('logo');
        $banner = $this->ossAttributePair('banner');
        $officialLogo = $this->schoolOfficialLogoPair();

        return [
            'school_code' => $this->resource->school_code,
            'official_logo' => $officialLogo['path'],
            'display_official_logo' => $officialLogo['display'],
            'short_name' => $this->resource->short_name,
            'province_code' => $this->resource->province_code,
            'city_code' => $this->resource->city_code,
            'district_code' => $this->resource->district_code,
            'address' => $this->resource->address,
            'contact_name' => $this->resource->contact_name,
            'contact_phone' => $this->resource->contact_phone,
            'contact_email' => $this->resource->contact_email,
            'qualification_file' => $this->resource->qualification_file,
            'competent_dept' => $this->resource->competent_dept,
            'education_levels' => $this->resource->education_levels ?? [],
            'education_level_labels' => $this->educationLevelLabels($this->resource->education_levels ?? []),
            'main_education_level' => $this->resource->main_education_level,
            'main_education_level_label' => RcEducationLevel::tryFrom((int) ($this->resource->main_education_level ?? 0))?->getLabel(),
            'logo' => $logo['path'],
            'display_logo' => $logo['display'],
            'banner' => $banner['path'],
            'display_banner' => $banner['display'],
            'allow_company_apply_activity' => $this->resource->allow_company_apply_activity,
            'allow_company_cooperate_apply' => $this->resource->allow_company_cooperate_apply,
            'campus_count' => $this->resource->campus_count,
            'department_count' => $this->resource->department_count,
            'cooperate_company_count' => $this->resource->cooperate_company_count,
            'activity_total' => $this->resource->activity_total,
            'intro' => $this->resource->intro,
            'status' => $this->resource->status->value,
            'status_label' => $this->resource->status->getLabel(),
            'remark' => $this->resource->remark,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }

    /**
     * @return array{path: ?string, display: ?string}
     */
    private function schoolOfficialLogoPair(): array
    {
        if (! $this->resource->relationLoaded('school') || ! $this->resource->school) {
            return ['path' => null, 'display' => null];
        }

        $raw = $this->resource->school->getAttributes()['official_logo'] ?? null;

        if (! is_string($raw) || $raw === '') {
            return ['path' => null, 'display' => null];
        }

        $path = ltrim($raw, '/');

        return [
            'path' => $path,
            'display' => AliyunOss::fromModel($this->resource->school, 'official_logo')->toDisplayUrl($path),
        ];
    }

    /**
     * @param  array<int, mixed>  $levels
     * @return array<int, string>
     */
    private function educationLevelLabels(array $levels): array
    {
        return collect($levels)
            ->map(static fn (mixed $level): ?string => RcEducationLevel::tryFrom((int) $level)?->getLabel())
            ->filter()
            ->values()
            ->all();
    }
}
