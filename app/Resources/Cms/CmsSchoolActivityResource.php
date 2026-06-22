<?php

namespace App\Resources\Cms;

use App\Models\Company;
use App\Models\Rc\SchoolActivity;
use App\Models\School;
use App\Resources\Concerns\SerializesOssAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CmsSchoolActivityResource extends JsonResource
{
    use SerializesOssAttributes;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof SchoolActivity) {
            return (array) $this->resource;
        }

        $coverImage = $this->ossAttributePair('cover_image');

        $data = [
            'id' => $this->resource->id,
            'type' => $this->resource->type->value,
            'type_label' => $this->resource->type->getLabel(),
            'title' => $this->resource->title,
            'cover_image' => $coverImage['path'],
            'display_cover_image' => $coverImage['display'],
            'province_code' => $this->resource->province_code,
            'city_code' => $this->resource->city_code,
            'district_code' => $this->resource->district_code,
            'address' => $this->resource->address,
            'register_start_date' => $this->resource->register_start_date,
            'register_end_date' => $this->resource->register_end_date,
            'start_time' => $this->resource->start_time,
            'end_time' => $this->resource->end_time,
            'organizer_type' => $this->resource->organizer_type?->value,
            'organizer_type_label' => $this->resource->organizer_type?->getLabel(),
            'organizer_id' => $this->resource->organizer_id,
            'organizer_name' => $this->resolveOrganizerName(),
            'status' => $this->resource->status->value,
            'status_label' => $this->resource->status->getLabel(),
            'is_hot' => $this->resource->is_hot,
            'sort' => $this->resource->sort,
        ];

        if ($this->shouldIncludeDetail($request)) {
            $data = [
                ...$data,
                'description' => $this->resource->description,
                'link_url' => $this->resource->link_url,
                'contact_name' => $this->resource->contact_name,
                'contact_phone' => $this->resource->contact_phone,
                'files' => $this->resource->files ?? [],
                'extra' => $this->resource->extra,
                'created_at' => $this->resource->created_at,
                'updated_at' => $this->resource->updated_at,
            ];
        }

        if ($this->resource->relationLoaded('schools')) {
            $data['schools'] = $this->resource->schools
                ->map(static fn (School $school): array => [
                    'id' => $school->id,
                    'school_code' => $school->school_code,
                    'name' => $school->name,
                ])
                ->values()
                ->all();
        }

        return $data;
    }

    private function shouldIncludeDetail(Request $request): bool
    {
        if ($request->route()?->named('school-activity.show')) {
            return true;
        }

        return (bool) $request->boolean('with_detail');
    }

    private function resolveOrganizerName(): ?string
    {
        if (! $this->resource->relationLoaded('organizer')) {
            return null;
        }

        $organizer = $this->resource->organizer;

        if ($organizer instanceof School || $organizer instanceof Company) {
            return $organizer->name;
        }

        return null;
    }
}
