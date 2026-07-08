<?php

namespace Tests\Feature\Filament;

use App\Enums\CompanyPlanStatus;
use App\Enums\CompanyStatus;
use App\Enums\SystemPlanStatus;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Models\Biz\Plan;
use App\Models\Company;
use App\Models\ShipCompanyPlan;
use App\Services\SysPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class CompaniesTableRefreshPlanActionTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    public function test_refresh_plan_action_is_hidden_when_company_has_no_current_plan(): void
    {
        $this->actingAsFilamentAdmin();

        $company = $this->createCompany();

        Livewire::test(ListCompanies::class)
            ->assertTableActionHidden('refreshPlan', $company);
    }

    public function test_refresh_plan_action_creates_new_ship_record(): void
    {
        $this->actingAsFilamentAdmin();

        $company = $this->createCompany();
        $plan = $this->createPlan();

        SysPlanService::make()->resolve($company, $plan, [
            'pay_amount' => 500.00,
            'remark' => '首次绑定',
        ]);

        $originalShipId = $company->companyPlans()->where('is_current', 1)->value('ship_id');

        Livewire::test(ListCompanies::class)
            ->callTableAction('refreshPlan', $company)
            ->assertNotified();

        $currentShipId = $company->companyPlans()->where('is_current', 1)->value('ship_id');

        $this->assertNotSame($originalShipId, $currentShipId);
        $this->assertSame(2, ShipCompanyPlan::query()->where('company_id', $company->id)->count());
        $this->assertDatabaseHas('oa_company_biz_plans', [
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'is_current' => 1,
            'status' => CompanyPlanStatus::Enabled->value,
        ]);
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

    private function createPlan(): Plan
    {
        return Plan::query()->create([
            'plan_name' => '标准套餐',
            'plan_code' => 'standard_plan',
            'price' => 999.00,
            'duration' => 365,
            'sort' => 1,
            'status' => SystemPlanStatus::Enabled,
        ]);
    }
}
