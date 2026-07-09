<?php

namespace Tests\Feature\Jobs;

use App\Enums\CompanyStatus;
use App\Enums\SystemPlanStatus;
use App\Jobs\BatchRebindCompanyPlansJob;
use App\Models\Company;
use App\Models\Oa\Biz\Plan;
use App\Models\ShipCompanyPlan;
use App\Services\SysPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchRebindCompanyPlansJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_refreshes_all_companies_with_current_plan(): void
    {
        $plan = $this->createPlan();
        $companyA = $this->createCompany(['credit_code' => '91360100MA0000000A', 'name' => '企业A']);
        $companyB = $this->createCompany(['credit_code' => '91360100MA0000000B', 'name' => '企业B']);
        $otherPlan = $this->createPlan([
            'plan_name' => '高级套餐',
            'plan_code' => 'premium_plan',
        ]);
        $companyC = $this->createCompany(['credit_code' => '91360100MA0000000C', 'name' => '企业C']);

        $service = SysPlanService::make();
        $service->resolve($companyA, $plan);
        $service->resolve($companyB, $plan);
        $service->resolve($companyC, $otherPlan);

        $originalShipIds = [
            $companyA->id => $companyA->companyPlans()->where('is_current', 1)->value('ship_id'),
            $companyB->id => $companyB->companyPlans()->where('is_current', 1)->value('ship_id'),
            $companyC->id => $companyC->companyPlans()->where('is_current', 1)->value('ship_id'),
        ];

        BatchRebindCompanyPlansJob::dispatchSync($plan->id);

        $this->assertNotSame(
            $originalShipIds[$companyA->id],
            $companyA->companyPlans()->where('is_current', 1)->value('ship_id'),
        );
        $this->assertNotSame(
            $originalShipIds[$companyB->id],
            $companyB->companyPlans()->where('is_current', 1)->value('ship_id'),
        );
        $this->assertSame(
            $originalShipIds[$companyC->id],
            $companyC->companyPlans()->where('is_current', 1)->value('ship_id'),
        );
        $this->assertSame(2, ShipCompanyPlan::query()->where('company_id', $companyA->id)->count());
        $this->assertSame(2, ShipCompanyPlan::query()->where('company_id', $companyB->id)->count());
        $this->assertSame(1, ShipCompanyPlan::query()->where('company_id', $companyC->id)->count());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createCompany(array $attributes = []): Company
    {
        return Company::query()->create(array_merge([
            'name' => '南昌示例科技有限公司',
            'credit_code' => '91360100MA0000000X',
            'legal_person' => '李四',
            'contact_phone' => '13900000000',
            'address' => '南昌市高新区示例路 88 号',
            'status' => CompanyStatus::Enabled,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createPlan(array $attributes = []): Plan
    {
        return Plan::query()->create(array_merge([
            'plan_name' => '标准套餐',
            'plan_code' => 'standard_plan',
            'price' => 999.00,
            'duration' => 365,
            'sort' => 1,
            'status' => SystemPlanStatus::Enabled,
        ], $attributes));
    }
}
