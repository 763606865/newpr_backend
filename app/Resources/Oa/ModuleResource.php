<?php

namespace App\Resources\Oa;

use App\Exceptions\BadRequestException;
use App\Services\CompanyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $companies = [];
        /** @var CompanyService $companyService */
        $companyService = CompanyService::make();

        try {
            $planData = $companyService->getCurrentBizPlanData($this->resource);
        } catch (BadRequestException) {
            $planData = null;
        }

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'plan' => $planData?->modulePlanPayload() ?? [],
            'menus' => $planData?->menus ?? [],
            'features' => $planData?->features ?? [],
        ];
    }
}
