<?php

namespace App\Resources\Oa;

use App\Models\Company;
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
        /** @var Company $company */
        foreach ($this->resource as $company) {
            $companyPlan = $company->companyPlans->where('is_current', '=', 1)->first();
            $shipCompanyPlan = $company->shipCompanyPlans->where('id', $companyPlan->ship_id)->first();
            $currentPlan = $company->currentPlans->first();
            $companies[] = [
                'id' => $company->id,
                'name' => $company->name,
                'plan' => [
                    'id' => $currentPlan?->id,
                    'plan_name' => $currentPlan?->plan_name,
                    'plan_code' => $currentPlan?->plan_code,
                ],
                'menus' => $this->normalizeMenus($shipCompanyPlan?->menus ?? []),
                'features' => $this->normalizeFeatures($shipCompanyPlan?->features ?? []),
            ];
        }

        return [
            'companies' => $companies,
        ];
    }

    private function normalizeMenus(array $menus = []): array
    {
        $normalized = [];
        foreach ($menus as $menu) {
            $normalized[] = [
                'id' => $menu['id'] ?? null,
                'parent_id' => $menu['parent_id'] ?? null,
                'menu_name' => $menu['menu_name'] ?? null,
                'menu_code' => $menu['menu_code'] ?? null,
                'menu_type' => $menu['menu_type'] ?? null,
                'path' => $menu['path'] ?? null,
                'component' => $menu['component'] ?? null,
                'icon' => $menu['icon'] ?? null,
                'sort' => $menu['sort'] ?? null,
                'visible' => $menu['visible'] ?? null,
                'style' => $menu['style'] ?? null,
                'extra' => $menu['extra'] ?? null,
            ];
        }
        $sortArr = array_column($normalized, 'sort');
        array_multisort($sortArr, SORT_ASC, $normalized);

        return $normalized;
    }

    private function normalizeFeatures(array $features = []): array
    {
        $normalized = [];
        foreach ($features as $feature) {
            $normalized[] = [
                'id' => $feature['id'] ?? null,
                'name' => $feature['feature_name'] ?? null,
                'code' => $feature['feature_code'] ?? null,
            ];
        }

        return $normalized;
    }
}
