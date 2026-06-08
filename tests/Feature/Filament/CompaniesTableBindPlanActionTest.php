<?php

namespace Tests\Feature\Filament;

use App\Enums\CompanyPlanStatus;
use App\Enums\CompanyStatus;
use App\Enums\SystemPlanStatus;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Models\Biz\Plan;
use App\Models\Company;
use Filament\Actions\ActionGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\InteractsWithFilamentAdmin;
use Tests\TestCase;

class CompaniesTableBindPlanActionTest extends TestCase
{
    use InteractsWithFilamentAdmin;
    use RefreshDatabase;

    public function test_bind_plan_action_is_visible_on_list_page(): void
    {
        $this->actingAsFilamentAdmin();

        $company = $this->createCompany();

        Livewire::test(ListCompanies::class)
            ->assertTableActionVisible('bindPlan', $company);
    }

    public function test_edit_and_delete_actions_are_grouped_under_more(): void
    {
        $this->actingAsFilamentAdmin();

        $company = $this->createCompany();

        $component = Livewire::test(ListCompanies::class);

        $component
            ->assertTableActionExists('edit', record: $company)
            ->assertTableActionExists('delete', record: $company);

        $recordActions = $component->instance()->getTable()->getRecordActions();

        $this->assertCount(4, $recordActions);
        $this->assertSame('bindPlan', $recordActions[1]->getName());
        $this->assertSame('operationLogs', $recordActions[2]->getName());
        $this->assertInstanceOf(ActionGroup::class, $recordActions[3]);
        $this->assertSame('更多', $recordActions[3]->getLabel());
    }

    public function test_bind_plan_action_creates_company_plan(): void
    {
        $this->actingAsFilamentAdmin();

        $company = $this->createCompany();
        $plan = $this->createPlan();

        Livewire::test(ListCompanies::class)
            ->callTableAction('bindPlan', $company, data: [
                'plan_id' => $plan->id,
                'pay_amount' => 888.00,
                'remark' => '测试绑定',
            ])
            ->assertNotified();

        $this->assertDatabaseHas('company_biz_plans', [
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'is_current' => 1,
            'status' => CompanyPlanStatus::Enabled->value,
        ]);

        $this->assertDatabaseHas('ship_company_biz_plans', [
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'pay_amount' => 888.00,
            'remark' => '测试绑定',
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
