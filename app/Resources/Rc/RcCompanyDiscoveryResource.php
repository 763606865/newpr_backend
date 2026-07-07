<?php

namespace App\Resources\Rc;

use App\Models\Company;
use App\Services\MetaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcCompanyDiscoveryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof Company) {
            return (array) $this->resource;
        }

        $profile = $this->resource->relationLoaded('profile') ? $this->resource->profile : null;
        $displayName = filled($profile?->short_name) ? (string) $profile->short_name : $this->resource->name;

        $data = [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'display_name' => $displayName,
            'address' => $this->resource->address,
        ];

        if ($profile) {
            $data['profile'] = (new RcCompanyProfileResource($profile))->resolve($request);

            if (filled($profile->city_code)) {
                $data['city_name'] = MetaService::make()->getCityFullName((string) $profile->city_code);
            }
        }

        if ($this->resource->relationLoaded('albums')) {
            $data['albums'] = RcCompanyAlbumResource::collection($this->resource->albums)->resolve($request);
        }

        if (array_key_exists('public_job_count', $this->resource->getAttributes())) {
            $data['stat'] = [
                'public_job_count' => (int) $this->resource->getAttribute('public_job_count'),
            ];
        }

        return $data;
    }
}
