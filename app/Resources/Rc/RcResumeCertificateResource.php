<?php

namespace App\Resources\Rc;

use App\Models\Rc\ResumeCertificate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcResumeCertificateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof ResumeCertificate) {
            return (array) $this->resource;
        }

        return [
            'id' => $this->resource->id,
            'resume_id' => $this->resource->resume_id,
            'user_id' => $this->resource->user_id,
            'name' => $this->resource->name,
            'cert_type' => $this->resource->cert_type?->value ?? $this->resource->cert_type,
            'cert_type_label' => $this->resource->cert_type?->getLabel(),
            'issuer' => $this->resource->issuer,
            'issue_date' => $this->resource->issue_date,
            'expire_date' => $this->resource->expire_date,
            'cert_no' => $this->resource->cert_no,
            'description' => $this->resource->description,
            'sort' => $this->resource->sort,
            'extra' => $this->resource->extra,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
