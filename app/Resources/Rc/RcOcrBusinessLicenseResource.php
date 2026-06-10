<?php

namespace App\Resources\Rc;

use App\Libs\Ocr\Data\BusinessLicenseResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RcOcrBusinessLicenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof BusinessLicenseResult) {
            return (array) $this->resource;
        }

        return [
            'credit_code' => $this->resource->creditCode,
            'company_name' => $this->resource->companyName,
            'company_type' => $this->resource->companyType,
            'business_address' => $this->resource->businessAddress,
            'legal_person' => $this->resource->legalPerson,
            'business_scope' => $this->resource->businessScope,
            'registered_capital' => $this->resource->registeredCapital,
            'registration_date' => $this->resource->registrationDate,
            'valid_period' => $this->resource->validPeriod,
            'valid_from_date' => $this->resource->validFromDate,
            'valid_to_date' => $this->resource->validToDate,
            'company_form' => $this->resource->companyForm,
            'request_id' => $this->resource->requestId,
        ];
    }
}
