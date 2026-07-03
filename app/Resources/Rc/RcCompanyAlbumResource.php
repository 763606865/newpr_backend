<?php

namespace App\Resources\Rc;

use App\Models\Rc\CompanyAlbum;
use App\Resources\Concerns\SerializesOssAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcCompanyAlbumResource extends JsonResource
{
    use SerializesOssAttributes;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof CompanyAlbum) {
            return (array) $this->resource;
        }

        $image = $this->ossAttributePair('image');

        return [
            'id' => $this->resource->id,
            'company_id' => $this->resource->company_id,
            'title' => $this->resource->title,
            'image' => $image['path'],
            'display_image' => $image['display'],
            'description' => $this->resource->description,
            'type' => $this->resource->type,
            'type_label' => $this->typeLabel($this->resource->type),
            'sort' => $this->resource->sort,
            'status' => $this->resource->status,
            'status_label' => $this->resource->status === 1 ? '启用' : '停用',
            'extra' => $this->resource->extra,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }

    private function typeLabel(?int $type): ?string
    {
        return match ($type) {
            1 => '办公环境',
            2 => '企业文化相册',
            3 => '企业荣誉相册',
            4 => '其他',
            default => null,
        };
    }
}
