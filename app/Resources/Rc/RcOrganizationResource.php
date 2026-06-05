<?php

namespace App\Resources\Rc;

use App\Models\Area;
use App\Models\Company;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcOrganizationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof Company) {
            return (new RcCompanyResource($this->resource))->toArray($request);
        }

        if ($this->resource instanceof School) {
            return (new RcSchoolResource($this->resource))->toArray($request);
        }

        if ($this->resource instanceof Area) {
            return (new RcAreaResource($this->resource))->toArray($request);
        }

        return (array) $this->resource;
    }
}
