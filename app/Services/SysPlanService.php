<?php

namespace App\Services;

use App\Enums\CompanyPlanStatus;
use App\Models\Biz\Plan;
use App\Models\Client\Feature;
use App\Models\Client\Menu;
use App\Models\Company;
use App\Models\CompanyPlan;
use App\Models\ShipCompanyPlan;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SysPlanService extends Service
{
    /**
     * @throws \Exception
     */
    public function resolve(Company $company, Plan $plan, array $ship = []): void
    {
        // 获取当前正在执行的套餐（非试用）
        /** @var null|CompanyPlan $currentPlan */
        $currentPlan = $company->companyPlans()->where('is_current', 1)->first();

        if (! $currentPlan) {
            $this->bindNewPlan($company, $plan, $ship);

            return;
        }

        // 续费相同套餐，延长时长
        if ($currentPlan->plan_id === $plan->id) {
            $ship = $this->extendCurrentPlan($currentPlan, $ship);
        }
        // 更换不同套餐，暂停当前，绑定新套餐
        $currentPlan->transitionToDisabled();
        $this->bindNewPlan($company, $plan, $ship);
    }

    private function bindNewPlan(Company $company, Plan $plan, array $ship): void
    {
        $rawFeatures = $plan->features()->with(['menu'])->get();
        $features = $this->normalizeFeatures($rawFeatures);
        $menus = $this->normalizeMenusFromFeatures($rawFeatures);
        $endTime = Carbon::now()->addDays($plan->duration)->addDays($ship['surplus_days'] ?? 0);
        // 创建ShipCompanyPlan
        $shipRecord = ShipCompanyPlan::create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->plan_name,
            'plan_code' => $plan->plan_code,
            'original_price' => $plan->price,
            'pay_amount' => $ship['pay_amount'] ?? $plan->price,
            'menus' => $menus,
            'features' => $features,
            'quota' => $ship['quota'] ?? [],
            'start_time' => Carbon::now(),
            'end_time' => $endTime,
            'remark' => $ship['remark'] ?? '',
            'extra' => $ship['extra'] ?? [],
        ]);

        // 创建CompanyPlan
        CompanyPlan::create([
            'company_id' => $company->id,
            'ship_id' => $shipRecord->id,
            'plan_id' => $plan->id,
            'start_time' => Carbon::now(),
            'end_time' => $endTime,
            'is_current' => 1,
            'status' => CompanyPlanStatus::Enabled,
            'extra' => [],
        ]);
    }

    private function extendCurrentPlan(CompanyPlan $oldPlan, array $ship): array
    {
        // 续时间
        $ship['surplus_days'] = Carbon::now()->diffInDays($oldPlan->end_time);

        return $ship;
    }

    /**
     * @param  Collection<int, Feature>  $features
     * @return array<int, array<string, mixed>>
     */
    private function normalizeFeatures(Collection $features): array
    {
        return $features
            ->filter(fn ($feature) => $feature instanceof Feature)
            ->unique(fn (Feature $feature) => (string) ($feature->feature_code ?: $feature->id))
            ->values()
            ->map(function (Feature $feature): array {
                return [
                    'id' => $feature->id,
                    'client_id' => $feature->client_id,
                    'feature_name' => $feature->feature_name,
                    'feature_code' => $feature->feature_code,
                    'menu_id' => $feature->menu_id,
                    'description' => $feature->description,
                    'status' => $feature->status,
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, Feature>  $features
     * @return array<int, array<string, mixed>>
     */
    private function normalizeMenusFromFeatures(Collection $features): array
    {
        return $features
            ->filter(fn ($feature) => $feature instanceof Feature)
            ->map(fn (Feature $feature) => $feature->menu)
            ->filter(fn ($menu) => $menu instanceof Menu)
            ->unique(fn (Menu $menu) => (string) ($menu->menu_code ?: $menu->id))
            ->sortBy('sort')
            ->values()
            ->map(function (Menu $menu): array {
                return [
                    'id' => $menu->id,
                    'client_id' => $menu->client_id,
                    'parent_id' => $menu->parent_id,
                    'menu_name' => $menu->menu_name,
                    'menu_code' => $menu->menu_code,
                    'menu_type' => $menu->menu_type,
                    'path' => $menu->path,
                    'component' => $menu->component,
                    'icon' => $menu->icon,
                    'sort' => $menu->sort,
                    'visible' => $menu->visible,
                    'style' => $menu->style,
                    'extra' => $menu->extra,
                ];
            })
            ->all();
    }
}
