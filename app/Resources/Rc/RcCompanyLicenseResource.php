<?php

namespace App\Resources\Rc;

use App\Models\CompanyLicense;
use App\Resources\Concerns\SerializesOssAttributes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcCompanyLicenseResource extends JsonResource
{
    use SerializesOssAttributes;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof CompanyLicense) {
            return (array) $this->resource;
        }

        $file = $this->ossAttributePair('file_url');

        return [
            'id' => $this->resource->id,
            'company_id' => $this->resource->company_id,
            'license_type' => $this->resource->license_type?->value,
            'name' => $this->resource->name,
            'license_no' => $this->resource->license_no,
            'issuer' => $this->resource->issuer,
            'issue_date' => $this->resource->issue_date,
            'expire_date' => $this->resource->expire_date,
            'file_url' => $file['path'],
            'display_file_url' => $file['display'],
            'file_name' => $this->resource->file_name,
            'file_ext' => $this->resource->file_ext,
            'is_primary' => $this->resource->is_primary,
            'sort' => $this->resource->sort,
            'status' => $this->resource->status,
            'remark' => $this->resource->remark,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
