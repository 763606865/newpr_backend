<?php

namespace App\Services;

use App\Enums\CompanyPlanStatus;
use App\Models\Oa\Company;
use App\Models\Oa\CompanyPlan;
use App\Models\Oa\ShipCompanyPlan;
use App\Models\Oa\System\Plan;
use Carbon\Carbon;

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

        if (!$currentPlan) {
            $this->bindNewPlan($company, $plan, $ship);
            return ;
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
        $features = $plan->features()->with(['menu'])->get();
        $menus = $features->pluck('menu')->flatten()->unique('menu_code');
        $endTime = Carbon::now()->addDays($plan->duration)->addDays($ship['surplus_days'] ?? 0);
        // 创建ShipCompanyPlan
        $shipRecord = ShipCompanyPlan::create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->plan_name,
            'plan_code' => $plan->plan_code,
            'original_price' => $plan->price,
            'pay_amount' => $ship['pay_amount'] ?? $plan->price,
            'menus' => $menus->all(),
            'features' => $features->all(),
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
}
