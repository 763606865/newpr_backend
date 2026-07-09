<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\Oa\Biz\Plan;
use App\Services\SysPlanService;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     */
    public function created(Company $company): void
    {
        // 查找试用方案
        $trialPlan = Plan::where('plan_code', 'trial_plan')->first();

        if ($trialPlan) {
            SysPlanService::make()->resolve($company, $trialPlan, [], true);
        }
    }
}
