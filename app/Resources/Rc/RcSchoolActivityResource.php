<?php

namespace App\Resources\Rc;

use App\Models\Rc\SchoolActivity;
use App\Resources\Concerns\SerializesOssAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcSchoolActivityResource extends JsonResource
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
            'description' => $this->resource->description,
            'link_url' => $this->resource->link_url,
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
            'contact_name' => $this->resource->contact_name,
            'contact_phone' => $this->resource->contact_phone,
            'status' => $this->resource->status->value,
            'status_label' => $this->resource->status->getLabel(),
            'is_hot' => $this->resource->is_hot,
            'sort' => $this->resource->sort,
            'files' => $this->resource->files ?? [],
            'extra' => $this->resource->extra,
            'remark' => $this->resource->remark,
            'booth_id' => $this->resource->booth_id,
            'invite_code' => $this->resource->invite_code,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];

        if (isset($this->resource->company_applications_count)) {
            $data['company_applications_count'] = (int) $this->resource->company_applications_count;
        }

        if (isset($this->resource->jobs_count)) {
            $data['jobs_count'] = (int) $this->resource->jobs_count;
        }

        if (isset($this->resource->activity_booths_count)) {
            $data['activity_booths_count'] = (int) $this->resource->activity_booths_count;
        }

        if ($this->resource->relationLoaded('activityBooths')) {
            $data['activity_booths'] = RcSchoolActivityBoothResource::collection($this->resource->activityBooths)
                ->resolve($request);
        }

        if ($this->resource->relationLoaded('booth') && $this->resource->booth) {
            $data['booth'] = (new RcSchoolBoothResource($this->resource->booth))->resolve($request);
        }

        return $data;
    }
}
